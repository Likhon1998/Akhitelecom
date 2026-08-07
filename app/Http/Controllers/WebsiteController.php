<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\AccountService;
use App\Services\OnlineOrderTrackingService;
use App\Services\StockService;
use App\Services\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsiteController extends Controller
{
    public function __construct(
        private WebsiteService $website,
        private AccountService $accounts,
        private StockService $stock,
        private OnlineOrderTrackingService $tracking,
    ) {}

    public function home()
    {
        return view('website.home', $this->website->homepageData());
    }

    public function shop(Request $request)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $query = $this->website->catalogQuery($shopId)->with(['category', 'brand']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->whereSlugOrId($request->category));
        }

        $brandIds = array_values(array_filter(array_map('intval', (array) $request->input('brands', []))));
        if ($brandIds) {
            $query->whereIn('brand_id', $brandIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $like = Schema::getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('brand_name', $like, "%{$search}%")
                    ->orWhere('sku', $like, "%{$search}%")
                    ->orWhere('barcode', $like, "%{$search}%")
                    ->orWhere('short_description', $like, "%{$search}%")
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', $like, "%{$search}%"))
                    ->orWhereHas('category', fn ($category) => $category->where('name', $like, "%{$search}%"));
            });
        }

        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', (float) $request->max_price);
        }

        $storages = array_values(array_filter(array_map('strval', (array) $request->input('storage', []))));
        if ($storages !== []) {
            $query->where(function ($q) use ($storages) {
                foreach ($storages as $value) {
                    $compact = memory_size_compact($value);
                    if ($compact === '') {
                        continue;
                    }
                    $q->orWhereRaw("LOWER(REPLACE(COALESCE(storage, ''), ' ', '')) = ?", [$compact]);
                }
            });
        }

        $rams = array_values(array_filter(array_map('strval', (array) $request->input('ram', []))));
        if ($rams !== []) {
            $query->where(function ($q) use ($rams) {
                foreach ($rams as $value) {
                    $compact = memory_size_compact($value);
                    if ($compact === '') {
                        continue;
                    }
                    $q->orWhereRaw("LOWER(REPLACE(COALESCE(ram, ''), ' ', '')) = ?", [$compact]);
                }
            });
        }

        if ($request->filter === 'deals') {
            $query->onSale();
        } elseif ($request->filter === 'new') {
            $query->newArrivals();
        } elseif ($request->filter === 'bestsellers') {
            $query->trending()->orderByDesc('review_count');
        }

        $sort = $request->query('sort', 'featured');
        match ($sort) {
            'price_asc' => $query->orderBy('selling_price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('selling_price')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'latest' => $query->latest('id'),
            'bestsellers' => $query->orderByDesc('is_best_seller')->orderByDesc('review_count')->latest('id'),
            default => $request->filter === 'new'
                ? $query->latest('id')
                : $query->orderByDesc('is_best_seller')->orderByDesc('review_count')->latest('id'),
        };

        $this->website->applyVariantGroupListing($query);
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = match ($request->filter) {
            'deals' => 'Deals',
            'new' => 'New Arrivals',
            'bestsellers' => 'Best Sellers',
            default => 'Shop',
        };

        if ($request->boolean('ajax') || $request->ajax()) {
            $countFrom = $products->firstItem() ?? 0;
            $countTo = $products->lastItem() ?? 0;
            $countTotal = $products->total();
            $settings = $this->website->settings();

            return response()->json([
                'html' => view('website.partials.shop-results', compact('products', 'settings'))->render(),
                'count_text' => "Showing {$countFrom}–{$countTo} of ".number_format($countTotal).' products',
                'title' => $pageTitle,
                'url' => $request->fullUrlWithoutQuery(['ajax']),
            ]);
        }

        $sidebar = $this->shopSidebarData($shopId);

        return view('website.shop', array_merge($this->website->homepageData(), $sidebar, compact(
            'products',
            'pageTitle',
            'sort',
        )));
    }

    public function searchSuggest(Request $request)
    {
        $shopId = $this->website->shopId();
        if (! $shopId) {
            return response()->json(['products' => []]);
        }

        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $limit = min(10, max(1, (int) $request->query('limit', 8)));
        $like = Schema::getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = $this->website->catalogQuery($shopId)->with(['category', 'brand']);

        if ($category !== '') {
            $query->whereHas('category', fn ($builder) => $builder->whereSlugOrId($category));
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q, $like) {
                $builder->where('name', $like, "%{$q}%")
                    ->orWhere('brand_name', $like, "%{$q}%")
                    ->orWhere('sku', $like, "%{$q}%")
                    ->orWhere('barcode', $like, "%{$q}%")
                    ->orWhere('short_description', $like, "%{$q}%")
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', $like, "%{$q}%"))
                    ->orWhereHas('category', fn ($category) => $category->where('name', $like, "%{$q}%"));
            })->orderBy('name');
        } else {
            $query->orderByDesc('is_best_seller')
                ->orderByDesc('review_count')
                ->latest();
        }

        $products = $this->website->dedupeVariantCollection($query->limit($limit * 3)->get(), $limit)
            ->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->storefrontDisplayName(),
            'brand' => $product->brand?->name ?? $product->brand_name,
            'price' => $product->currentPrice(),
            'image' => $this->website->productImageUrl($product),
            'url' => route('website.product', $product),
            'in_stock' => $product->stock_quantity > 0,
        ]);

        return response()->json([
            'products' => $products,
            'mode' => $q === '' ? 'best' : 'search',
        ]);
    }

    public function category(string $slug, Request $request)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $category = Category::where('shop_id', $shopId)
            ->whereSlugOrId($slug)
            ->firstOrFail();

        if (blank($category->slug)) {
            $category->save();
        }

        $filterConfig = \App\Support\CategoryFilterConfig::for($category);
        $showSidebar = (bool) ($filterConfig['enabled'] ?? false);

        // Category pages can show out-of-stock when filters are on (availability facet)
        $query = Product::query()
            ->where('shop_id', $shopId)
            ->where('category_id', $category->id)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->with(['category', 'brand']);

        if (! $showSidebar) {
            $query->where('stock_quantity', '>', 0);
        }

        $this->applyCategoryFilters($query, $request, $filterConfig, $category);

        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('selling_price'),
            'price_desc' => $query->orderByDesc('selling_price'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $this->website->applyVariantGroupListing($query);
        $products = $query->paginate(12)->withQueryString();

        $sidebarFacets = $showSidebar
            ? $this->buildSidebarFacets($category, $filterConfig)
            : [];

        $priceBoundsQuery = Product::query()
            ->where('shop_id', $shopId)
            ->where('category_id', $category->id)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'));

        $sidebar = $this->shopSidebarData($shopId);
        // Keep price slider scoped to this category's range.
        $sidebar['priceBounds'] = [
            'min' => (float) ((clone $priceBoundsQuery)->min('selling_price') ?? 0),
            'max' => (float) ((clone $priceBoundsQuery)->max('selling_price') ?? 0),
        ];

        return view('website.shop', array_merge($this->website->homepageData(), $sidebar, [
            'products' => $products,
            'activeCategory' => $category,
            'pageTitle' => $category->name,
            'pageSubtitle' => $category->description ?: 'Browse all products in this category.',
            'showSidebar' => $showSidebar,
            'filterConfig' => $filterConfig,
            'sidebarFacets' => $sidebarFacets,
            'sort' => $sort,
        ]));
    }

    /**
     * Shared category / brand / price data for the shop listing sidebar.
     */
    protected function shopSidebarData(int $shopId): array
    {
        $this->website->linkOrphanProductsToBrands($shopId);
        $this->website->mergeDuplicateBrands($shopId);

        $catalog = $this->website->catalogQuery($shopId);

        $categories = Category::where('shop_id', $shopId)
            ->orderBy('name')
            ->withCount(['products as published_count' => function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)
                    ->where('stock_quantity', '>', 0)
                    ->where(function ($qq) {
                        $qq->where('is_published', true)->orWhereNull('is_published');
                    });
            }])
            ->get();

        $visibleBrandProducts = function ($q) use ($shopId) {
            $q->where('shop_id', $shopId)
                ->where('stock_quantity', '>', 0)
                ->where(function ($qq) {
                    $qq->where('is_published', true)->orWhereNull('is_published');
                });
        };

        $brands = Brand::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->withCount([
                'products as published_count' => $visibleBrandProducts,
                'products as products_count' => $visibleBrandProducts,
            ])
            ->get()
            ->filter(fn (Brand $b) => (int) ($b->products_count ?? $b->published_count ?? 0) > 0)
            ->values();

        $storageOptions = unique_memory_sizes(
            (clone $catalog)
                ->whereNotNull('storage')
                ->where('storage', '!=', '')
                ->distinct()
                ->pluck('storage')
        );

        $ramOptions = unique_memory_sizes(
            (clone $catalog)
                ->whereNotNull('ram')
                ->where('ram', '!=', '')
                ->distinct()
                ->pluck('ram')
        );

        return [
            'categories' => $categories,
            'brands' => $brands,
            'storageOptions' => $storageOptions,
            'ramOptions' => $ramOptions,
            'categoryTotal' => (clone $catalog)->count(),
            'priceBounds' => [
                'min' => (float) ((clone $catalog)->min('selling_price') ?? 0),
                'max' => (float) ((clone $catalog)->max('selling_price') ?? 0),
            ],
        ];
    }

    protected function applyCategoryFilters($query, Request $request, array $filterConfig, Category $category): void
    {
        if (! empty($filterConfig['price_enabled'])) {
            if ($request->filled('min_price')) {
                $query->where('selling_price', '>=', (float) $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('selling_price', '<=', (float) $request->max_price);
            }
        }

        foreach ($filterConfig['groups'] ?? [] as $group) {
            if (empty($group['enabled'])) {
                continue;
            }

            $key = $group['key'] ?? '';
            $selected = array_filter((array) $request->query($key, []));
            if ($selected === [] || $key === '') {
                continue;
            }

            $type = $group['type'] ?? 'custom';

            if ($type === 'availability') {
                $query->where(function ($q) use ($selected) {
                    foreach ($selected as $value) {
                        $q->orWhere(function ($inner) use ($value) {
                            if ($value === 'in_stock') {
                                $inner->where('stock_quantity', '>', 0)
                                    ->where(function ($a) {
                                        $a->whereNull('availability')
                                            ->orWhere('availability', 'in_stock');
                                    });
                            } elseif ($value === 'out_of_stock') {
                                $inner->where('stock_quantity', '<=', 0)
                                    ->where(function ($a) {
                                        $a->whereNull('availability')
                                            ->orWhere('availability', 'out_of_stock')
                                            ->orWhere('availability', 'in_stock');
                                    });
                            } else {
                                $inner->where('availability', $value);
                            }
                        });
                    }
                });
                continue;
            }

            if ($type === 'brand') {
                $query->where(function ($q) use ($selected, $category) {
                    $brands = Brand::where('shop_id', $category->shop_id)->get();
                    foreach ($selected as $value) {
                        $match = $brands->first(fn ($b) => \Illuminate\Support\Str::slug($b->name, '_') === $value
                            || strtolower($b->name) === str_replace('_', ' ', strtolower($value)));
                        if ($match) {
                            $q->orWhere('brand_id', $match->id)->orWhere('brand_name', $match->name);
                        } else {
                            $label = str_replace('_', ' ', $value);
                            $q->orWhereRaw('LOWER(COALESCE(brand_name, \'\')) = ?', [strtolower($label)]);
                        }
                    }
                });
                continue;
            }

            if ($type === 'storage') {
                $query->where(function ($q) use ($selected) {
                    foreach ($selected as $value) {
                        $compact = memory_size_compact(str_replace('_', ' ', (string) $value));
                        if ($compact === '') {
                            continue;
                        }
                        $q->orWhereRaw("LOWER(REPLACE(COALESCE(storage, ''), ' ', '')) = ?", [$compact]);
                    }
                });
                continue;
            }

            if ($type === 'ram') {
                $query->where(function ($q) use ($selected) {
                    foreach ($selected as $value) {
                        $compact = memory_size_compact(str_replace('_', ' ', (string) $value));
                        if ($compact === '') {
                            continue;
                        }
                        $q->orWhereRaw("LOWER(REPLACE(COALESCE(ram, ''), ' ', '')) = ?", [$compact]);
                    }
                });
                continue;
            }

            if ($type === 'color') {
                $query->where(function ($q) use ($selected) {
                    foreach ($selected as $value) {
                        $label = str_replace('_', ' ', $value);
                        $q->orWhereRaw('LOWER(REPLACE(COALESCE(color, \'\'), \' \', \'_\')) = ?', [strtolower($value)])
                            ->orWhereRaw('LOWER(color) = ?', [strtolower($label)]);
                    }
                });
                continue;
            }

            // Custom attributes JSON
            $query->where(function ($q) use ($selected, $key) {
                foreach ($selected as $value) {
                    $q->orWhere("filter_attributes->{$key}", $value)
                        ->orWhereJsonContains("filter_attributes->{$key}", $value);
                }
            });
        }
    }

    protected function buildSidebarFacets(Category $category, array $filterConfig): array
    {
        $facets = [];
        foreach ($filterConfig['groups'] ?? [] as $group) {
            if (empty($group['enabled'])) {
                continue;
            }

            $type = $group['type'] ?? 'custom';
            $options = $group['options'] ?? [];

            if (in_array($type, ['brand', 'storage', 'ram', 'color', 'custom'], true) && $options === []) {
                $options = \App\Support\CategoryFilterConfig::facetValues($category, $type, $group['key'] ?? '')
                    ->all();
            }

            if ($options === [] && $type !== 'availability') {
                continue;
            }

            $facets[] = [
                'key' => $group['key'],
                'label' => $group['label'],
                'type' => $type,
                'options' => $options,
            ];
        }

        return $facets;
    }

    public function brand(string $slug)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $brand = Brand::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->withCount(['products' => function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)
                    ->where('stock_quantity', '>', 0)
                    ->where(function ($qq) {
                        $qq->where('is_published', true)->orWhereNull('is_published');
                    });
            }])
            ->get()
            ->filter(fn ($b) => \Illuminate\Support\Str::slug($b->name) === $slug)
            // Prefer the brand row that actually has products (handles samsung / Samsung duplicates).
            ->sortByDesc('products_count')
            ->first();

        abort_unless($brand, 404);

        $this->website->linkOrphanProductsToBrands($shopId);
        $this->website->mergeDuplicateBrands($shopId);

        // Re-resolve after merge in case the chosen row was absorbed.
        $brand = Brand::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->get()
            ->filter(fn ($b) => \Illuminate\Support\Str::slug($b->name) === $slug)
            ->sortByDesc(fn ($b) => Product::where('brand_id', $b->id)->count())
            ->first() ?? $brand;

        $brandQuery = $this->website->catalogQuery($shopId)
            ->where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)
                    ->orWhereRaw('LOWER(TRIM(COALESCE(brand_name, \'\'))) = ?', [strtolower(trim($brand->name))]);
            })
            ->with(['category', 'brand'])
            ->latest();
        $this->website->applyVariantGroupListing($brandQuery);
        $products = $brandQuery->paginate(12);

        return view('website.shop', array_merge($this->website->homepageData(), $this->shopSidebarData($shopId), [
            'products' => $products,
            'activeBrand' => $brand,
            'pageTitle' => $brand->name,
            'pageSubtitle' => 'Shop all products from ' . $brand->name . '.',
            'sort' => 'latest',
        ]));
    }

    public function product(Request $request, Product $product)
    {
        $shopId = $this->website->shopId();
        $published = $product->is_published !== false;
        abort_unless($shopId && $product->shop_id === $shopId && $published, 404);

        $product->loadMissing(['category', 'brand']);

        $relatedQuery = $this->website->catalogQuery($shopId)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id);
        if ($product->variant_group) {
            $relatedQuery->where(function ($q) use ($product) {
                $q->whereNull('variant_group')
                    ->orWhere('variant_group', '!=', $product->variant_group);
            });
        }
        $this->website->applyVariantGroupListing($relatedQuery);
        $related = $relatedQuery->take(4)->get();

        $variantOptions = $this->website->productVariantOptions($product);

        $reviews = \App\Models\CmsReview::where('shop_id', $shopId)
            ->where('is_published', true)
            ->where(function ($q) use ($product) {
                $q->where('product_id', $product->id)->orWhereNull('product_id');
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        $home = $this->website->homepageData();
        $settings = $home['settings'] ?? $this->website->settings();

        if ($request->boolean('ajax') || $request->ajax()) {
            $displayName = $product->storefrontDisplayName();

            $storeName = data_get($settings, 'store_name', 'Shop');

            return response()->json([
                'html' => view('website.partials.product-live', compact(
                    'product',
                    'related',
                    'reviews',
                    'variantOptions',
                    'settings'
                ))->render(),
                'url' => route('website.product', $product),
                'title' => $displayName.' | '.$storeName,
            ]);
        }

        return view('website.product', array_merge($home, compact('product', 'related', 'reviews', 'variantOptions')));
    }

    public function page(string $slug)
    {
        $page = $this->website->publishedPage($slug);
        abort_unless($page, 404);

        return view('website.cms-page', array_merge($this->website->homepageData(), compact('page')));
    }

    public function blogs(Request $request)
    {
        $data = $this->website->blogPageData(
            $request->query('q'),
            $request->query('category')
        );

        return view('website.blogs', $data);
    }

    public function blog(string $slug)
    {
        $blog = $this->website->publishedBlog($slug);
        abort_unless($blog, 404);

        $blog->increment('views_count');
        $blog->refresh();

        $related = \App\Models\CmsBlog::where('shop_id', $blog->shop_id)
            ->published()
            ->where('id', '!=', $blog->id)
            ->when($blog->category_id, fn ($q) => $q->where('category_id', $blog->category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('website.blog-show', array_merge($this->website->homepageData(), [
            'blog' => $blog,
            'relatedPosts' => $related,
            'popularPosts' => $this->website->popularBlogs(3),
            'blogCategories' => $this->website->blogCategories(),
        ]));
    }

    public function subscribeNewsletter(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        \App\Models\CmsNewsletterSubscriber::firstOrCreate(
            ['shop_id' => $shopId, 'email' => strtolower(trim($request->email))]
        );

        return back()->with('newsletter_success', 'Thanks for subscribing!');
    }

    public function faqs(Request $request)
    {
        return view('website.faqs', $this->website->faqPageData(
            $request->query('q'),
            $request->query('category')
        ));
    }

    public function contact()
    {
        return view('website.contact', $this->website->contactPageData());
    }

    public function submitContact(Request $request)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'order_number' => 'nullable|string|max:80',
            'message' => 'required|string|max:5000',
        ]);

        \App\Models\CmsContactMessage::create([
            'shop_id' => $shopId,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'order_number' => $data['order_number'] ?? null,
            'message' => $data['message'],
            'is_read' => false,
        ]);

        return back()->with('contact_success', 'Thanks! Your message has been sent. We\'ll get back to you soon.');
    }

    public function trackOrder()
    {
        return view('website.track-order', array_merge($this->website->homepageData(), [
            'tracking' => null,
            'invoiceNo' => request('invoice'),
            'phone' => request('phone'),
        ]));
    }

    public function trackOrderLookup(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => ['required', 'string', 'max:64'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $invoice = trim($data['invoice_no']);
        $phone = Customer::normalizePhone($data['phone']);

        $order = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->where('invoice_no', $invoice)
            ->with(['customer', 'items.product', 'statusLogs'])
            ->first();

        if (! $order || ! $order->customer || Customer::normalizePhone($order->customer->phone) !== $phone) {
            return back()
                ->withInput()
                ->with('error', 'No order found for that Order ID and phone number.');
        }

        $payload = $this->tracking->trackingPayload($order);

        return view('website.track-order', array_merge($this->website->homepageData(), [
            'tracking' => $payload,
            'invoiceNo' => $invoice,
            'phone' => $data['phone'],
        ]));
    }

    public function wishlist()
    {
        return view('website.wishlist', $this->website->homepageData());
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        if (! $user?->isStorefrontCustomer()) {
            return response()->json(['success' => false, 'message' => 'Please sign in to place an order.', 'auth_required' => true], 401);
        }

        $shopId = $this->website->shopId();
        if (! $shopId || empty($request->cart)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty or store unavailable.']);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string|max:1000',
        ]);

        $customer = Customer::where('shop_id', $shopId)
            ->where('user_id', $user->id)
            ->first();

        if (! $customer) {
            $customer = Customer::create([
                'shop_id' => $shopId,
                'user_id' => $user->id,
                'name' => $request->customer_name,
                'email' => $user->email,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
            ]);
        } else {
            $customer->update([
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
                'email' => $user->email,
            ]);
        }

        $user->update(['name' => $request->customer_name]);

        $deliveryFee = 0; // Server-controlled; free COD delivery for now (ignore client fee).

        $shopAdmin = \App\Models\User::where('shop_id', $shopId)->whereIn('role', ['admin', 'shop_owner', 'Shop Owner'])->first();
        $fallbackUserId = $shopAdmin?->id ?? $user->id;

        // Resolve cart against live catalog prices/stock (never trust client prices).
        $resolvedLines = [];
        $subtotal = 0.0;

        foreach ((array) $request->cart as $item) {
            $productId = (int) ($item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            if ($productId < 1 || $qty < 1) {
                return response()->json(['success' => false, 'message' => 'Invalid cart item.']);
            }

            $product = Product::where('shop_id', $shopId)->find($productId);
            if (! $product || $product->is_published === false) {
                return response()->json(['success' => false, 'message' => 'A product in your cart is no longer available.']);
            }
            if ($product->stock_quantity < $qty) {
                return response()->json([
                    'success' => false,
                    'message' => "Not enough stock for {$product->name}. Only {$product->stock_quantity} left.",
                ]);
            }

            $unitPrice = $product->currentPrice();
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $resolvedLines[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $lineTotal,
            ];
        }

        if ($resolvedLines === []) {
            return response()->json(['success' => false, 'message' => 'Cart is empty or store unavailable.']);
        }

        $finalTotal = $subtotal + $deliveryFee;

        try {
            DB::beginTransaction();

            $invoiceNo = Order::nextWebInvoiceNo($shopId);
            if ($invoiceNo === '') {
                throw new \RuntimeException('Could not generate an order ID. Please try again.');
            }

            $order = Order::create([
                'shop_id' => $shopId,
                'user_id' => $fallbackUserId,
                'invoice_no' => $invoiceNo,
                'customer_id' => $customer->id,
                'total_amount' => $finalTotal,
                'delivery_charge' => $deliveryFee,
                'paid_amount' => 0,
                'payment_method' => 'cash_on_delivery',
                'status' => 'pending',
                'counter_id' => null,
            ]);

            if (! $order->id || blank($order->invoice_no)) {
                throw new \RuntimeException('Order was created without an order ID. Please try again.');
            }

            foreach ($resolvedLines as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            // Stock is committed when packing starts (processing), not at COD placement.
            $order->load('items.product');
            $this->accounts->postWebSale($order);
            $this->tracking->logInitialPlacement($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'invoice' => $order->invoice_no,
                'message' => 'Order placed successfully. Your Order ID is '.$order->invoice_no.'.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Order failed: '.$e->getMessage()]);
        }
    }
}
