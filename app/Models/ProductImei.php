<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImei extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD = 'sold';

    public const STATUS_RESERVED = 'reserved';

    protected $fillable = [
        'product_id',
        'imei',
        'imei_2',
        'status',
        'order_id',
        'order_item_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public static function normalize(string $imei): string
    {
        return preg_replace('/\s+/', '', trim($imei)) ?: '';
    }

    /** Matches a phone by either of its IMEIs. */
    public function scopeMatching($query, string $imei)
    {
        return $query->where(fn ($q) => $q->where('imei', $imei)->orWhere('imei_2', $imei));
    }
}
