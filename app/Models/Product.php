<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'category_id', 'brand_id', 'name', 'barcode', 'sku',
        'variant_group', 'color', 'color_hex', 'storage',
        'cost_price', 'selling_price', 'original_price',
        'sale_price', 'sale_starts_at', 'sale_ends_at',
        'stock_quantity', 'availability', 'filter_attributes', 'alert_quantity', 'reorder_quantity',
        'image', 'image_2', 'image_3',
        'short_description', 'brand_name', 'rating', 'review_count',
        'is_best_seller', 'is_featured', 'is_new_arrival', 'is_published',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'rating' => 'decimal:1',
        'is_best_seller' => 'boolean',
        'is_featured' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_published' => 'boolean',
        'filter_attributes' => 'array',
    ];

    /** Stored gallery paths (new table first, then legacy columns). */
    public function imagePaths(): array
    {
        $gallery = $this->relationLoaded('galleryImages')
            ? $this->galleryImages
            : $this->galleryImages()->get();

        if ($gallery->isNotEmpty()) {
            return $gallery->pluck('path')->filter()->values()->all();
        }

        return array_values(array_filter([
            $this->image,
            $this->image_2,
            $this->image_3,
        ]));
    }

    public function galleryImages()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    /** Products marked as new arrivals for shop filter + homepage. */
    public function scopeNewArrivals($query)
    {
        return $query->where('is_new_arrival', true);
    }

    /** Whether the storefront should show a "New" badge. */
    public function showsAsNew(): bool
    {
        return (bool) $this->is_new_arrival;
    }

    /** Active timed sale: offer price lower than list price within the window. */
    public function isOnSale(?CarbonInterface $at = null): bool
    {
        $at = $at ?? now();

        if ($this->sale_price === null || $this->sale_starts_at === null || $this->sale_ends_at === null) {
            return false;
        }

        if ((float) $this->sale_price >= (float) $this->selling_price) {
            return false;
        }

        return $at->greaterThanOrEqualTo($this->sale_starts_at)
            && $at->lessThanOrEqualTo($this->sale_ends_at);
    }

    /** Price charged now (sale price when active, otherwise list price). */
    public function currentPrice(): float
    {
        return $this->isOnSale()
            ? (float) $this->sale_price
            : (float) $this->selling_price;
    }

    /** List / compare-at price shown struck through during an active sale. */
    public function compareAtPrice(): ?float
    {
        return $this->isOnSale() ? (float) $this->selling_price : null;
    }

    public function discountPercent(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }

        $base = (float) $this->selling_price;
        if ($base <= 0) {
            return 0;
        }

        return (int) round((1 - ((float) $this->sale_price / $base)) * 100);
    }

    public function scopeOnSale($query, ?CarbonInterface $at = null)
    {
        $at = $at ?? now();

        return $query
            ->whereNotNull('sale_price')
            ->whereNotNull('sale_starts_at')
            ->whereNotNull('sale_ends_at')
            ->whereRaw('sale_price < selling_price')
            ->where('sale_starts_at', '<=', $at)
            ->where('sale_ends_at', '>=', $at);
    }

    public function clearSale(): void
    {
        $this->forceFill([
            'sale_price' => null,
            'sale_starts_at' => null,
            'sale_ends_at' => null,
        ])->save();
    }

    public function applySale(float $salePrice, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        $this->forceFill([
            'sale_price' => round($salePrice, 2),
            'sale_starts_at' => Carbon::parse($startsAt),
            'sale_ends_at' => Carbon::parse($endsAt),
        ])->save();
    }

    /** Sibling products in the same variant group (other colors / storage). */
    public function variantSiblings()
    {
        if (!$this->variant_group) {
            return collect();
        }

        return static::query()
            ->where('shop_id', $this->shop_id)
            ->where('variant_group', $this->variant_group)
            ->where('id', '!=', $this->id)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->where('stock_quantity', '>', 0)
            ->get();
    }

    public function displayColor(): ?string
    {
        return $this->color ?: null;
    }

    public function swatchHex(): string
    {
        if ($this->color_hex && preg_match('/^#[0-9A-Fa-f]{6}$/', $this->color_hex)) {
            return $this->color_hex;
        }

        $map = [
            'red' => '#dc2626',
            'blue' => '#2563eb',
            'black' => '#1e293b',
            'white' => '#f8fafc',
            'green' => '#16a34a',
            'gold' => '#ca8a04',
            'silver' => '#94a3b8',
            'gray' => '#64748b',
            'grey' => '#64748b',
            'pink' => '#ec4899',
            'purple' => '#9333ea',
            'orange' => '#ea580c',
            'yellow' => '#eab308',
            'natural titanium' => '#d4cfc8',
            'phantom black' => '#2d2d2d',
            'white titanium' => '#e8e6e3',
            'blue titanium' => '#5b7a9d',
            'black titanium' => '#3a3a3a',
        ];

        $key = strtolower(trim($this->color ?? ''));

        return $map[$key] ?? '#cbd5e1';
    }
}