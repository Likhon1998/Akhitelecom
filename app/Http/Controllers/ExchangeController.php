<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImei;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeController extends Controller
{
    public function processExchange(Request $request, Order $order)
    {
        $user = Auth::user();

        if ($order->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized order.');
        }

        if (! $user->isAdminUser() && $user->counter_id && (int) $order->counter_id !== (int) $user->counter_id) {
            abort(403, 'You can only exchange sales from your counter.');
        }

        if ($order->isOnlineOrder() && ! $user->isAdminUser()) {
            abort(403, 'Only admins can exchange online orders.');
        }

        $request->validate([
            'return_product_id' => 'required|exists:products,id',
            'return_qty' => 'required|integer|min:1',
            'return_imeis' => 'nullable|array',
            'return_imeis.*' => 'string|max:32',
        ]);

        $returnProduct = Product::where('shop_id', $user->shop_id)->findOrFail($request->return_product_id);
        $returnQty = (int) $request->return_qty;
        $returnImeis = [];

        if ($returnProduct->requires_imei) {
            $soldOnOrder = ProductImei::where('order_id', $order->id)
                ->where('product_id', $returnProduct->id)
                ->where('status', ProductImei::STATUS_SOLD)
                ->pluck('imei');

            if ($soldOnOrder->isNotEmpty()) {
                $returnImeis = collect((array) $request->input('return_imeis', []))
                    ->map(fn ($v) => ProductImei::normalize((string) $v))
                    ->filter(fn ($v) => $soldOnOrder->contains($v))
                    ->unique()
                    ->values()
                    ->all();
                if ($returnImeis === []) {
                    return back()->with('error', 'Choose the IMEI of the phone the customer is returning.');
                }
                $returnQty = count($returnImeis);
            }
        }

        return redirect()->route('pos.index', array_filter([
            'exchange_order' => $order->id,
            'return_product' => $returnProduct->id,
            'return_qty' => $returnQty,
            'return_imeis' => $returnImeis !== [] ? implode(',', $returnImeis) : null,
            'credit' => $returnProduct->selling_price * $returnQty,
        ], fn ($v) => $v !== null));
    }
}
