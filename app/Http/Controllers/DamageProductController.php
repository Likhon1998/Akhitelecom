<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\Product;
use App\Models\ProductImei;
use App\Services\AccountService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DamageProductController extends Controller
{
    use ShopScoped;

    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
    ) {}

    public function index(Request $request)
    {
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get();
        $damages = \App\Models\StockMovement::where('shop_id', $this->shopId())
            ->where('reason', 'damage')
            ->with(['product', 'user'])
            ->latest()
            ->paginate(15);

        $todayDamagedQty = (int) \App\Models\StockMovement::where('shop_id', $this->shopId())
            ->where('reason', 'damage')
            ->whereDate('created_at', now()->toDateString())
            ->sum('quantity');

        $imeiStock = ProductImei::stockMap($this->shopId())['store'];

        return view('supply.damage-products.index', compact('products', 'damages', 'todayDamagedQty', 'imeiStock'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'quantity' => 'required|integer|min:1',
            'reference' => 'required|string|max:255',
            'imeis' => 'nullable|array',
            'imeis.*' => 'nullable|string|max:32',
        ]);

        $product = Product::where('shop_id', $this->shopId())->findOrFail($request->product_id);

        try {
            $this->stock->transaction(function () use ($request, $product) {
                $this->accounts->ensureShopAccounts($this->shopId());

                if ($product->requires_imei) {
                    ProductImei::moveStock($product, $request->input('imeis', []), (int) $request->quantity, ProductImei::STATUS_AVAILABLE, ProductImei::STATUS_DAMAGED);
                }

                $movement = $this->stock->apply(
                    $product,
                    'out',
                    (int) $request->quantity,
                    $request->reference,
                    'damage',
                    Auth::id(),
                    'damage_product',
                    null,
                    $this->stock->defaultStore($this->shopId())?->id,
                );

                $this->accounts->postInventoryAdjustment($movement);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('supply.damage-products.index')->with('success', 'Damaged stock written off. Inventory and accounts updated.');
    }
}
