<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsBlog;
use App\Models\CmsBlogCategory;
use App\Models\CmsFaq;
use App\Models\CmsFaqCategory;
use App\Models\CmsPage;
use App\Models\CmsReview;
use App\Models\HeroSlide;
use App\Models\NavigationLink;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Models\Shop;
use App\Models\SiteFeature;
use App\Models\SiteSetting;

class WebsiteService
{
    public function shop(): ?Shop
    {
        $settings = SiteSetting::query()->first();
        if ($settings?->default_shop_id) {
            $shop = Shop::query()->where('id', $settings->default_shop_id)->where('is_active', true)->first();
            if ($shop) {
                return $shop;
            }
        }

        return Shop::query()->where('is_active', true)->orderBy('id')->first();
    }

    public function shopId(): ?int
    {
        return $this->shop()?->id;
    }

    public function settings(): object
    {
        $site = SiteSetting::current();
        $shop = $this->shop();

        return (object) [
            'store_name' => $site->store_name ?: ($shop?->name ?? config('app.name', 'GAGET STORE')),
            'logo_path' => $site->logo_path,
            'currency_code' => $site->currency_code ?: 'USD',
            'currency_symbol' => $site->currency_symbol ?: '$',
            'special_offer_text' => $site->special_offer_text ?: 'Special Offer!',
            'trusted_by_text' => $site->trusted_by_text ?: 'Trusted by thousands of customers',
            'contact_email' => $site->contact_email ?: $shop?->email,
            'contact_phone' => $site->contact_phone ?: $shop?->phone,
            'contact_address' => $site->contact_address ?: $shop?->address,
            'social_links' => $site->social_links ?? [],
            'blog_hero_kicker' => $site->blog_hero_kicker,
            'blog_hero_title' => $site->blog_hero_title,
            'blog_hero_subtitle' => $site->blog_hero_subtitle,
            'blog_hero_image' => $site->blog_hero_image,
            'blog_newsletter_title' => $site->blog_newsletter_title,
            'blog_newsletter_text' => $site->blog_newsletter_text,
            'faq_hero_title' => $site->faq_hero_title,
            'faq_hero_subtitle' => $site->faq_hero_subtitle,
            'faq_help_title' => $site->faq_help_title,
            'faq_help_text' => $site->faq_help_text,
            'faq_help_button' => $site->faq_help_button,
            'contact_hero_kicker' => $site->contact_hero_kicker,
            'contact_hero_title' => $site->contact_hero_title,
            'contact_hero_subtitle' => $site->contact_hero_subtitle,
            'contact_chat_title' => $site->contact_chat_title,
            'contact_chat_text' => $site->contact_chat_text,
            'contact_chat_status' => $site->contact_chat_status,
            'contact_email_card_title' => $site->contact_email_card_title,
            'contact_email_card_text' => $site->contact_email_card_text,
            'contact_phone_card_title' => $site->contact_phone_card_title,
            'contact_phone_card_text' => $site->contact_phone_card_text,
            'contact_hours_title' => $site->contact_hours_title,
            'contact_hours_weekday' => $site->contact_hours_weekday,
            'contact_hours_weekend' => $site->contact_hours_weekend,
            'contact_form_title' => $site->contact_form_title,
            'contact_form_subtitle' => $site->contact_form_subtitle,
            'contact_map_embed' => $site->contact_map_embed,
            'contact_website_url' => $site->contact_website_url,
            'contact_newsletter_title' => $site->contact_newsletter_title,
            'contact_newsletter_text' => $site->contact_newsletter_text,
        ];
    }

    public function homepageData(): array
    {
        $shopId = $this->shopId();
        $settings = $this->settings();

        if (!$shopId) {
            return $this->emptyHomepage($settings);
        }

        $visibleProducts = fn ($q) => $q->where('stock_quantity', '>', 0)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'));

        $categories = Category::where('shop_id', $shopId)
            ->whereHas('products', $visibleProducts)
            ->withCount(['products' => $visibleProducts])
            ->orderBy('name')
            ->take(10)
            ->get();

        $bestSellers = $this->catalogQuery($shopId)
            ->with(['category', 'brand'])
            ->orderByDesc('is_best_seller')
            ->orderByDesc('review_count')
            ->latest()
            ->take(10)
            ->get();

        $flashSaleProducts = $this->catalogQuery($shopId)
            ->with(['category', 'brand'])
            ->whereNotNull('original_price')
            ->whereColumn('original_price', '>', 'selling_price')
            ->orderByRaw('(original_price - selling_price) / NULLIF(original_price, 0) DESC')
            ->take(5)
            ->get();

        $newArrivals = $this->catalogQuery($shopId)
            ->with(['category', 'brand'])
            ->newArrivals()
            ->latest('id')
            ->take(5)
            ->get();

        $trendingProducts = $this->catalogQuery($shopId)
            ->with(['category', 'brand'])
            ->orderByDesc('is_best_seller')
            ->orderByDesc('review_count')
            ->latest()
            ->take(5)
            ->get();

        $brands = Brand::where('shop_id', $shopId)
            ->where('is_active', true)
            ->whereHas('products', $visibleProducts)
            ->withCount(['products' => $visibleProducts])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'settings' => $settings,
            'shop' => $this->shop(),
            'heroSlides' => $this->resolveHeroSlides($shopId),
            'features' => SiteFeature::where('shop_id', $shopId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'categories' => $categories,
            'allCategories' => Category::where('shop_id', $shopId)
                ->withCount(['products' => fn ($q) => $q->where('stock_quantity', '>', 0)])
                ->orderBy('name')
                ->get(),
            'promoBanners' => PromoBanner::where('shop_id', $shopId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'bestSellers' => $bestSellers,
            'flashSaleProducts' => $flashSaleProducts,
            'newArrivals' => $newArrivals,
            'trendingProducts' => $trendingProducts,
            'brands' => $brands,
            'mainNav' => NavigationLink::where('shop_id', $shopId)->where('location', 'main_nav')->where('is_active', true)->orderBy('sort_order')->get(),
            'topBarNav' => NavigationLink::where('shop_id', $shopId)->where('location', 'top_bar')->where('is_active', true)->orderBy('sort_order')->get(),
            'featuredReviews' => CmsReview::where('shop_id', $shopId)->where('is_published', true)->where('is_featured', true)->orderBy('sort_order')->take(6)->get(),
            'footerPages' => CmsPage::where('shop_id', $shopId)->where('is_published', true)->where('show_in_footer', true)->orderBy('sort_order')->get(),
            'latestBlogs' => CmsBlog::where('shop_id', $shopId)->published()->with('category')->latest('published_at')->take(3)->get(),
        ];
    }

    private function emptyHomepage(object $settings): array
    {
        return [
            'settings' => $settings,
            'shop' => null,
            'heroSlides' => collect(),
            'features' => collect(),
            'categories' => collect(),
            'allCategories' => collect(),
            'promoBanners' => collect(),
            'bestSellers' => collect(),
            'flashSaleProducts' => collect(),
            'newArrivals' => collect(),
            'trendingProducts' => collect(),
            'brands' => collect(),
            'mainNav' => collect(),
            'topBarNav' => collect(),
            'featuredReviews' => collect(),
            'footerPages' => collect(),
            'latestBlogs' => collect(),
        ];
    }

    public function publishedPage(string $slug): ?CmsPage
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return null;
        }

        return CmsPage::where('shop_id', $shopId)->where('slug', $slug)->where('is_published', true)->first();
    }

    public function publishedBlog(string $slug): ?CmsBlog
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return null;
        }

        return CmsBlog::where('shop_id', $shopId)->published()->with('category')->where('slug', $slug)->first();
    }

    public function publishedFaqs(?string $search = null, ?string $categorySlug = null)
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        $this->ensureFaqDefaults($shopId);

        $query = CmsFaq::where('shop_id', $shopId)
            ->published()
            ->with('faqCategory')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('faqCategory', fn ($q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        return $query->get();
    }

    public function faqCategories()
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        $this->ensureFaqDefaults($shopId);

        return CmsFaqCategory::where('shop_id', $shopId)
            ->where('is_active', true)
            ->withCount(['faqs' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function ensureFaqDefaults(int $shopId): void
    {
        if (CmsFaqCategory::where('shop_id', $shopId)->exists()) {
            return;
        }

        $created = [];
        foreach ([
            ['name' => 'Orders & Payments', 'slug' => 'orders-payments', 'icon' => 'cart', 'sort_order' => 1],
            ['name' => 'Shipping & Delivery', 'slug' => 'shipping-delivery', 'icon' => 'truck', 'sort_order' => 2],
            ['name' => 'Returns & Refunds', 'slug' => 'returns-refunds', 'icon' => 'refresh', 'sort_order' => 3],
            ['name' => 'Products & Warranty', 'slug' => 'products-warranty', 'icon' => 'shield', 'sort_order' => 4],
            ['name' => 'Account & Security', 'slug' => 'account-security', 'icon' => 'lock', 'sort_order' => 5],
            ['name' => 'Promotions & Discounts', 'slug' => 'promotions-discounts', 'icon' => 'tag', 'sort_order' => 6],
            ['name' => 'Others', 'slug' => 'others', 'icon' => 'help', 'sort_order' => 7],
        ] as $row) {
            $created[$row['slug']] = CmsFaqCategory::create(array_merge($row, [
                'shop_id' => $shopId,
                'is_active' => true,
            ]));
        }

        if (CmsFaq::where('shop_id', $shopId)->exists()) {
            return;
        }

        $samples = [
            ['orders-payments', 'How do I place an order?', "Browse our store, add items to your cart, then sign in to checkout. Enter your delivery details and confirm — we currently accept cash on delivery (COD)."],
            ['orders-payments', 'What payment methods do you accept?', 'Online orders are cash on delivery (COD) only. Pay the delivery agent when your package arrives.'],
            ['orders-payments', 'Can I change or cancel my order after placing it?', 'Contact support as soon as possible with your order number. We can change or cancel orders that have not yet been prepared for shipping.'],
            ['shipping-delivery', 'How can I track my order?', 'Use Track Order with your Order ID and phone number, or sign in and open My Orders for status updates.'],
            ['shipping-delivery', 'Do you offer international shipping?', 'We currently deliver within our service area. Contact support if you need help with your location.'],
            ['returns-refunds', 'What is your return policy?', 'Most products can be returned within 30 days if unused and in original packaging. Some items may be excluded — see the product page or contact support.'],
            ['returns-refunds', 'How do I request a return or refund?', 'Go to Help Center or contact support with your order number and reason. Once approved, follow the return shipping instructions we send you.'],
            ['products-warranty', 'Are your products covered by warranty?', 'Yes. Eligible gadgets include manufacturer or store warranty as shown on each product page. Keep your invoice for warranty claims.'],
        ];

        foreach ($samples as $i => [$slug, $question, $answer]) {
            $cat = $created[$slug] ?? null;
            CmsFaq::create([
                'shop_id' => $shopId,
                'category_id' => $cat?->id,
                'category' => $cat?->name,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $i + 1,
                'is_published' => true,
            ]);
        }
    }

    public function faqPageData(?string $search = null, ?string $categorySlug = null): array
    {
        return array_merge($this->homepageData(), [
            'faqs' => $this->publishedFaqs($search, $categorySlug),
            'faqCategories' => $this->faqCategories(),
            'faqSearch' => $search,
            'activeFaqCategory' => $categorySlug,
        ]);
    }

    public function contactPageData(): array
    {
        return $this->homepageData();
    }

    public function publishedBlogs(int $perPage = 6, ?string $search = null, ?string $categorySlug = null)
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $query = CmsBlog::where('shop_id', $shopId)
            ->published()
            ->with('category')
            ->latest('published_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function featuredBlog(): ?CmsBlog
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return null;
        }

        return CmsBlog::where('shop_id', $shopId)
            ->published()
            ->with('category')
            ->where('is_featured', true)
            ->latest('published_at')
            ->first()
            ?? CmsBlog::where('shop_id', $shopId)->published()->with('category')->latest('published_at')->first();
    }

    public function popularBlogs(int $limit = 4)
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        return CmsBlog::where('shop_id', $shopId)
            ->published()
            ->with('category')
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take($limit)
            ->get();
    }

    public function blogCategories()
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        return CmsBlogCategory::where('shop_id', $shopId)
            ->where('is_active', true)
            ->withCount(['blogs' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function blogPageData(?string $search = null, ?string $categorySlug = null): array
    {
        $featured = $this->featuredBlog();
        $blogs = $this->publishedBlogs(6, $search, $categorySlug);

        return array_merge($this->homepageData(), [
            'featuredPost' => $featured,
            'blogs' => $blogs,
            'blogCategories' => $this->blogCategories(),
            'popularPosts' => $this->popularBlogs(4),
            'blogSearch' => $search,
            'activeBlogCategory' => $categorySlug,
        ]);
    }

    /** Homepage posters from CMS only (no product auto-link). */
    public function resolveHeroSlides(?int $shopId = null)
    {
        $shopId ??= $this->shopId();
        if (! $shopId) {
            return collect();
        }

        return HeroSlide::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function formatPrice(?float $amount, ?object $settings = null): string
    {
        $settings ??= $this->settings();
        $symbol = $settings->currency_symbol ?? '$';

        if ($amount === null) {
            return $symbol . '0.00';
        }

        return $symbol . number_format((float) $amount, 2);
    }

    /** Products visible on the public storefront. */
    public function catalogQuery(?int $shopId = null)
    {
        $shopId ??= $this->shopId();

        return Product::query()
            ->with('galleryImages')
            ->where('shop_id', $shopId)
            ->where('stock_quantity', '>', 0)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            });
    }

    public function productImageUrl($product): string
    {
        $urls = $this->productImageUrls($product);

        return $urls[0] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80';
    }

    /** All product gallery URLs (uploaded images, then config/fallback). */
    public function productImageUrls($product): array
    {
        $urls = [];
        foreach ($product->imagePaths() as $path) {
            $urls[] = public_storage_url($path);
        }

        if ($urls === []) {
            $fallback = config('website_assets.products.' . $product->barcode)
                ?? config('website_assets.products.' . \Illuminate\Support\Str::slug($product->name))
                ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80';
            $urls[] = $fallback;
        }

        return $urls;
    }

    /**
     * Color & storage pickers for a product detail page.
     * Returns ['colors' => [...], 'storages' => [...]] with url, label, active, hex.
     */
    public function productVariantOptions(Product $product): array
    {
        if (!$product->variant_group) {
            return ['colors' => [], 'storages' => []];
        }

        $siblings = Product::query()
            ->where('shop_id', $product->shop_id)
            ->where('variant_group', $product->variant_group)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->where('stock_quantity', '>', 0)
            ->get();

        $all = $siblings->push($product)->unique('id');

        $colors = [];
        foreach ($all->whereNotNull('color')->unique('color') as $p) {
            $match = $all->first(function ($item) use ($p, $product) {
                return strtolower($item->color) === strtolower($p->color)
                    && $product->storage
                    && $item->storage
                    && strtolower($item->storage) === strtolower($product->storage);
            }) ?? $all->first(fn ($item) => strtolower($item->color) === strtolower($p->color));

            if (!$match) {
                continue;
            }

            $colors[] = [
                'label' => $p->color,
                'hex' => $match->swatchHex(),
                'url' => route('website.product', $match),
                'active' => strtolower($match->color ?? '') === strtolower($product->color ?? ''),
                'product_id' => $match->id,
            ];
        }

        $storages = [];
        $currentColor = $product->color;
        $colorFiltered = $currentColor
            ? $all->filter(fn ($p) => strtolower($p->color ?? '') === strtolower($currentColor))
            : $all;

        foreach ($colorFiltered->whereNotNull('storage')->unique('storage')->sortBy('storage') as $p) {
            $storages[] = [
                'label' => $p->storage,
                'url' => route('website.product', $p),
                'active' => $p->id === $product->id,
                'product_id' => $p->id,
            ];
        }

        return [
            'colors' => collect($colors)->unique('label')->values()->all(),
            'storages' => array_values($storages),
        ];
    }

    public function categoryImageUrl($category): ?string
    {
        if ($category->image_path ?? null) {
            return public_storage_url($category->image_path);
        }

        $product = Product::query()
            ->where('shop_id', $category->shop_id)
            ->where('category_id', $category->id)
            ->where('stock_quantity', '>', 0)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->whereNotNull('image')
            ->latest()
            ->first();

        if ($product) {
            return $this->productImageUrl($product);
        }

        return null;
    }
}
