<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImei extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD = 'sold';

    public const STATUS_RESERVED = 'reserved';

    /** Held in a warehouse (location_id), not sellable until transferred to the store. */
    public const STATUS_WAREHOUSE = 'warehouse';

    public const STATUS_DAMAGED = 'damaged';

    /** Taken out by a stock adjustment. */
    public const STATUS_REMOVED = 'removed';

    public const STATUS_SUPPLIER_RETURN = 'supplier_return';

    /** Phones that left stock without a sale and may come back (repaired, re-delivered, recounted). */
    public const RETURNABLE_STATUSES = [self::STATUS_DAMAGED, self::STATUS_REMOVED, self::STATUS_SUPPLIER_RETURN];

    protected $fillable = [
        'product_id',
        'imei',
        'imei_2',
        'status',
        'location_id',
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

    /** 15 digits, or a 6–32 character serial for gadgets without an IMEI. */
    public static function isValidFormat(string $imei): bool
    {
        return ctype_digit($imei) ? strlen($imei) === 15 : (bool) preg_match('/^[A-Za-z0-9-]{6,32}$/', $imei);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => 'in stock',
            self::STATUS_SOLD => 'sold',
            self::STATUS_RESERVED => 'reserved',
            self::STATUS_WAREHOUSE => 'in the warehouse',
            self::STATUS_DAMAGED => 'marked damaged',
            self::STATUS_REMOVED => 'removed by a stock adjustment',
            self::STATUS_SUPPLIER_RETURN => 'returned to the supplier',
            default => $status,
        };
    }

    /**
     * Phones on hand for the supply forms:
     * ['store' => [productId => [{imei, imei_2}]], 'warehouse' => [locationId => [productId => [...]]]].
     */
    public static function stockMap(int $shopId): array
    {
        $map = ['store' => [], 'warehouse' => []];

        self::query()
            ->whereIn('status', [self::STATUS_AVAILABLE, self::STATUS_WAREHOUSE])
            ->whereHas('product', fn ($q) => $q->where('shop_id', $shopId)->where('requires_imei', true))
            ->orderBy('imei')
            ->get(['product_id', 'imei', 'imei_2', 'status', 'location_id'])
            ->each(function (self $row) use (&$map) {
                $phone = ['imei' => $row->imei, 'imei_2' => $row->imei_2];
                if ($row->status === self::STATUS_AVAILABLE) {
                    $map['store'][$row->product_id][] = $phone;
                } else {
                    $map['warehouse'][$row->location_id][$row->product_id][] = $phone;
                }
            });

        return $map;
    }

    /**
     * Move chosen phones of a product from one state to another, e.g. available → damaged,
     * or warehouse (location A) → available. Each IMEI may be IMEI 1 or IMEI 2.
     * Exactly $quantity distinct IMEIs are required.
     *
     * @param  iterable<string>  $imeis
     */
    public static function moveStock(
        Product $product,
        iterable $imeis,
        int $quantity,
        string $fromStatus,
        string $toStatus,
        ?int $fromLocationId = null,
        ?int $toLocationId = null,
    ): void {
        $list = collect($imeis)->map(fn ($v) => self::normalize((string) $v))->filter()->unique()->values();

        if ($list->count() !== $quantity) {
            throw new \InvalidArgumentException(
                "Choose the IMEI of each phone for {$product->name} ({$quantity} needed, {$list->count()} chosen)."
            );
        }

        foreach ($list as $imei) {
            $row = self::where('product_id', $product->id)
                ->matching($imei)
                ->where('status', $fromStatus)
                ->when($fromStatus === self::STATUS_WAREHOUSE, fn ($q) => $q->where('location_id', $fromLocationId))
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $where = $fromStatus === self::STATUS_WAREHOUSE ? 'in that warehouse' : self::statusLabel($fromStatus);
                throw new \InvalidArgumentException("IMEI {$imei} of {$product->name} is not {$where}.");
            }

            $row->update([
                'status' => $toStatus,
                'location_id' => $toStatus === self::STATUS_WAREHOUSE ? $toLocationId : null,
            ]);
        }
    }

    /**
     * Register arriving phones (purchase receive, stock adjustment in). A phone of this product that
     * left stock without a sale (damaged, removed, returned to supplier) comes back; anything else must be new.
     *
     * @param  iterable<array{imei?: mixed, imei2?: mixed}|string>  $phones
     */
    public static function receivePhones(
        Product $product,
        iterable $phones,
        int $quantity,
        string $status = self::STATUS_AVAILABLE,
        ?int $locationId = null,
    ): void {
        $rows = [];
        $seen = [];
        foreach ($phones as $entry) {
            $imei = self::normalize((string) (is_array($entry) ? ($entry['imei'] ?? '') : $entry));
            $imei2 = is_array($entry) ? self::normalize((string) ($entry['imei2'] ?? '')) : '';
            if ($imei === '' && $imei2 === '') {
                continue;
            }
            if ($imei === '') {
                throw new \InvalidArgumentException("Enter IMEI 1 for the phone with IMEI 2 {$imei2}.");
            }
            foreach (array_filter([$imei, $imei2]) as $code) {
                if (! self::isValidFormat($code)) {
                    throw new \InvalidArgumentException("\"{$code}\" is not a valid IMEI — an IMEI has exactly 15 digits.");
                }
                if (isset($seen[$code])) {
                    throw new \InvalidArgumentException("IMEI {$code} is entered twice.");
                }
                $seen[$code] = true;
            }
            $rows[] = [$imei, $imei2 === '' ? null : $imei2];
        }

        if (count($rows) !== $quantity) {
            throw new \InvalidArgumentException(
                "Enter the IMEI of each phone for {$product->name} ({$quantity} needed, ".count($rows).' entered).'
            );
        }

        $attributes = [
            'status' => $status,
            'location_id' => $status === self::STATUS_WAREHOUSE ? $locationId : null,
            'order_id' => null,
            'order_item_id' => null,
        ];

        foreach ($rows as [$imei, $imei2]) {
            $row = self::with('product:id,name')->matching($imei)->lockForUpdate()->first();

            if ($row && ((int) $row->product_id !== (int) $product->id || ! in_array($row->status, self::RETURNABLE_STATUSES, true))) {
                throw new \InvalidArgumentException(match (true) {
                    (int) $row->product_id !== (int) $product->id => "IMEI {$imei} already belongs to \"".($row->product?->name ?? 'another product').'".',
                    $row->status === self::STATUS_SOLD => "IMEI {$imei} was sold — take customer returns through a refund or exchange.",
                    default => "IMEI {$imei} is already ".self::statusLabel($row->status).'.',
                });
            }

            if ($imei2 !== null && self::matching($imei2)->when($row, fn ($q) => $q->whereKeyNot($row->id))->exists()) {
                throw new \InvalidArgumentException("IMEI {$imei2} already belongs to another phone.");
            }

            if ($row) {
                $row->update($attributes + ($imei2 !== null ? ['imei_2' => $imei2] : []));
            } else {
                $product->imeis()->create($attributes + ['imei' => $imei, 'imei_2' => $imei2]);
            }
        }
    }

    /** Matches a phone by either of its IMEIs. */
    public function scopeMatching($query, string $imei)
    {
        return $query->where(fn ($q) => $q->where('imei', $imei)->orWhere('imei_2', $imei));
    }

    /**
     * Mark phones of $productId as sold on an order line. Each IMEI may be IMEI 1 or IMEI 2.
     *
     * @param  iterable<string>  $imeis
     * @return list<string> IMEIs that were not in stock (strict mode throws instead)
     */
    public static function markSold(int $productId, iterable $imeis, int $orderId, ?int $orderItemId, bool $strict = true, string $productName = ''): array
    {
        $missing = [];
        foreach ($imeis as $imei) {
            $imei = self::normalize((string) $imei);
            if ($imei === '') {
                continue;
            }
            $row = self::where('product_id', $productId)->matching($imei)->available()->lockForUpdate()->first();
            if (! $row) {
                if ($strict) {
                    throw new \InvalidArgumentException("IMEI {$imei} is not in stock for {$productName}.");
                }
                $missing[] = $imei;

                continue;
            }
            $row->update([
                'status' => self::STATUS_SOLD,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
            ]);
        }

        return $missing;
    }

    /**
     * Put phones sold on an order back in stock (refund / return / cancel / exchange).
     *
     * @param  list<string>  $only  limit to these IMEIs (1 or 2); empty = all phones of the product on the order
     */
    public static function releaseForOrder(int $orderId, int $productId, array $only = [], ?int $limit = null): int
    {
        $query = self::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('status', self::STATUS_SOLD)
            ->orderBy('id');

        if ($only !== []) {
            $query->where(fn ($q) => $q->whereIn('imei', $only)->orWhereIn('imei_2', $only));
        }
        if ($limit !== null) {
            $query->limit($limit);
        }

        $released = 0;
        foreach ($query->lockForUpdate()->get() as $row) {
            $row->update(['status' => self::STATUS_AVAILABLE, 'order_id' => null, 'order_item_id' => null]);
            $released++;
        }

        return $released;
    }
}
