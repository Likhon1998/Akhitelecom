<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CounterSessionService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use InvalidArgumentException;

class PosController extends Controller
{
    public function __construct(
        protected AccountService $accounts,
        protected StockService $stock,
    ) {}
    /**
     * Load the POS Terminal
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->canAccessPos()) {
            return redirect()->route('dashboard')->with('error', 'Access Denied: You must be assigned to a specific Counter before you can access the POS terminal. Please contact your Admin.');
        }

        // Cashiers assigned to a counter must enter today's opening cash first
        if ($user->requiresDailyOpeningBalance() && ! $user->hasTodayOpenSession()) {
            return redirect()
                ->route('counters.sessions.open-today')
                ->with('error', 'Enter your opening cash for today before using the POS.');
        }

        $shopId = $user->shop_id;
        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get();
        $brands = Brand::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brandById = $brands->keyBy('id');
        $brandsSortedByNameLength = $brands->sortByDesc(fn (Brand $b) => mb_strlen($b->name))->values();

        $products = Product::where('shop_id', $shopId)
            ->with(['category:id,name', 'brand:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($brandById, $brandsSortedByNameLength) {
                [$brandId, $brandName] = $this->resolvePosBrand($product, $brandById, $brandsSortedByNameLength);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'image' => $product->image,
                    'category_id' => $product->category_id,
                    'brand_id' => $brandId,
                    'category_name' => $product->category?->name,
                    'brand_name' => $brandName,
                ];
            })
            ->values();

        $openSession = null;
        $posCounters = collect();
        $defaultPosCounterId = null;

        if ($user->counter_id) {
            $counter = Counter::find($user->counter_id);
            $openSession = $counter
                ? app(CounterSessionService::class)->currentOpen($counter)
                : null;
        } elseif ($user->isAdminUser()) {
            // Admin may only sell on a free till they opened themselves (with opening balance).
            $openSession = $user->adminOpenPosSession();

            if (! $openSession) {
                return redirect()
                    ->route('counters.sessions.open-today')
                    ->with('error', 'Select an unassigned counter and enter opening cash before using POS.');
            }

            $openSession->loadMissing('counter');
            $defaultPosCounterId = (int) $openSession->counter_id;
            $posCounters = collect([[
                'id' => (int) $openSession->counter_id,
                'name' => $openSession->counter?->name ?? ('Counter #'.$openSession->counter_id),
                'has_open_session' => true,
                'opened_by_admin' => true,
            ]]);
        }

        // 🚀 CATCH EXCHANGE PARAMETERS (If redirected from Sales Ledger)
        $exchangeOrder = $request->query('exchange_order');
        $returnProduct = $request->query('return_product');
        $returnQty = $request->query('return_qty');
        $credit = $request->query('credit', 0);

        return view('pos.index', compact(
            'categories',
            'brands',
            'products',
            'exchangeOrder',
            'returnProduct',
            'returnQty',
            'credit',
            'openSession',
            'posCounters',
            'defaultPosCounterId',
        ));
    }

    /**
     * Process the sale, save customer, and record stock movements
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canAccessPos()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction Blocked: No Counter assigned to your account.'
            ], 403);
        }

        try {
            $counterId = $this->resolvePosCounterId($user, $request->input('counter_id'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        if ($user->isAdminUser() && ! $user->adminOpenPosSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Open an unassigned counter with opening cash before making sales.',
                'redirect' => route('counters.sessions.open-today'),
            ], 403);
        }

        if ($user->requiresDailyOpeningBalance() && ! $user->hasOpenCounterSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Open your cash session before making sales.',
                'redirect' => route('counters.sessions.open-today'),
            ], 403);
        }

        $request->validate([
            'cart' => 'required|array',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
            'cash_paid' => 'nullable|numeric|min:0',
            'card_paid' => 'nullable|numeric|min:0',
            'mobile_paid' => 'nullable|numeric|min:0',
            'customer_phone' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'counter_id' => 'nullable|integer',
        ]);

        $shopId = $user->shop_id;

        try {
            DB::beginTransaction();

            $totalAmount = 0;

            // 1. Calculate the exact total from the database
            foreach ($request->cart as $item) {
                $product = Product::where('shop_id', $shopId)->findOrFail($item['id']);
                if ($product->stock_quantity < $item['qty']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                $totalAmount += $product->selling_price * $item['qty'];
            }

            // 🚀 EXCHANGE MATH & SECURITY
            $isExchange = $request->is_exchange ?? false;
            $exchangeCredit = (float) ($request->exchange_credit ?? 0);

            if ($isExchange && $totalAmount < $exchangeCredit) {
                throw new \Exception("Exchange Blocked: Cart total must equal or exceed the return credit. No cash refunds allowed.");
            }

            // After exchange credit, before discount
            $afterCredit = max(0, $totalAmount - $exchangeCredit);

            // Cart/coupon discount from POS; if customer pays under due, remainder = less (discount)
            $paidAmount = max(0, (float) $request->paid_amount);
            $requestedDiscount = max(0, (float) ($request->discount_amount ?? 0));
            $discountAmount = min($afterCredit, $requestedDiscount);
            $payableAmount = max(0, $afterCredit - $discountAmount);

            if ($paidAmount < $payableAmount) {
                $discountAmount = min($afterCredit, $afterCredit - $paidAmount);
                $payableAmount = $paidAmount;
            }

            $changeAmount = max(0, $paidAmount - $payableAmount);

            // 2. Customer Handling (same CRM table as website shoppers — match by phone)
            $customerId = null;
            if (!empty($request->customer_phone)) {
                $phone = Customer::normalizePhone($request->customer_phone);
                $customer = Customer::where('shop_id', $shopId)->wherePhone($phone)->first();

                if ($customer) {
                    $updates = [];
                    if (!empty($request->customer_name) && $customer->name !== $request->customer_name) {
                        $updates['name'] = $request->customer_name;
                    }
                    if ($customer->phone !== $phone) {
                        $updates['phone'] = $phone;
                    }
                    if ($updates !== []) {
                        $customer->update($updates);
                    }
                    $customerId = $customer->id;
                } else {
                    $newCustomer = Customer::create([
                        'shop_id' => $shopId,
                        'phone' => $phone,
                        'name' => $request->customer_name ?? 'Guest User',
                    ]);
                    $customerId = $newCustomer->id;
                }
            }

            // 3. Unique invoice inside the open transaction (locked)
            $invoiceNo = Order::nextPosInvoiceNo($shopId, 'INV');

            $cashPaid = $request->has('cash_paid') ? max(0, (float) $request->cash_paid) : null;
            $cardPaid = $request->has('card_paid') ? max(0, (float) $request->card_paid) : null;
            $mobilePaid = $request->has('mobile_paid') ? max(0, (float) $request->mobile_paid) : null;

            $clientUuid = trim((string) $request->input('client_uuid', ''));
            if ($clientUuid !== '') {
                $existing = Order::where('shop_id', $shopId)
                    ->where('offline_client_uuid', $clientUuid)
                    ->first();
                if ($existing) {
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'order_id' => $existing->id,
                        'invoice_no' => $existing->invoice_no,
                        'change' => (float) $existing->change_amount,
                        'total_amount' => (float) $existing->total_amount,
                        'duplicate' => true,
                    ]);
                }
            }

            // 4. Create the Main Order
            $order = Order::create([
                'shop_id' => $shopId,
                'user_id' => $user->id, 
                'counter_id' => $counterId,
                'customer_id' => $customerId, 
                'invoice_no' => $invoiceNo,
                'offline_client_uuid' => $clientUuid !== '' ? $clientUuid : null,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'paid_amount' => $paidAmount,
                'cash_paid' => $cashPaid,
                'card_paid' => $cardPaid,
                'mobile_paid' => $mobilePaid,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                
                // 🔒 FLAGS: Marks this as an exchange so it cannot be refunded
                'is_exchange_receipt' => $isExchange,
                'exchange_for_order_id' => $request->exchange_for_order_id,
                
                // 🚀 NEW FIELDS: Store exactly what was returned for the receipt
                'return_product_id' => $isExchange ? $request->return_product_id : null,
                'return_qty' => $isExchange ? $request->return_qty : null,
                'exchange_credit' => $isExchange ? $exchangeCredit : null,
            ]);

            // 5. Save Items & Decrease Inventory
            foreach ($request->cart as $item) {
                $product = Product::where('shop_id', $shopId)->findOrFail($item['id']);
                $subtotal = $product->selling_price * $item['qty'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit_price' => $product->selling_price,
                    'subtotal' => $subtotal,
                ]);

                $this->stock->recordSale(
                    $product,
                    (int) $item['qty'],
                    'Sale - ' . $invoiceNo,
                    $user->id,
                    'order',
                    $order->id,
                );
            }

            // 6. Restock returned item on exchange
            if ($isExchange && $request->return_product_id) {
                $returnProduct = Product::where('shop_id', $shopId)->find($request->return_product_id);
                if ($returnProduct && (int) $request->return_qty > 0) {
                    $this->stock->restockForDocument(
                        $returnProduct,
                        (int) $request->return_qty,
                        'Exchange return for ' . $invoiceNo,
                        'exchange_return',
                        $order->id,
                        'exchange_return',
                        $user->id,
                    );
                }
            }

            $order->load('items.product', 'counter');
            $this->accounts->postOrderSale($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully!',
                'order_id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'change' => $order->change_amount,
                'paid_amount' => $order->paid_amount,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'receipt_url' => route('pos.receipt', $order),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * View the printable receipt
     */
    public function receipt(Order $order)
    {
        if ($order->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized Access');
        }
        
        $order->load('items.product', 'user', 'customer', 'shop', 'counter');
        
        // 🚀 FETCH THE EXACT RETURNED PRODUCT FOR THE RECEIPT
        $returnProduct = null;
        if ($order->is_exchange_receipt && $order->return_product_id) {
            $returnProduct = Product::find($order->return_product_id);
        }

        return view('pos.receipt', compact('order', 'returnProduct'));
    }

    /**
     * Look up existing customer by phone number
     */
    public function lookupCustomer(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $phone = $request->phone;

        if (!$phone) {
            return response()->json(['found' => false]);
        }

        $customer = Customer::where('shop_id', $shopId)
            ->wherePhone($phone)
            ->first();

        if ($customer) {
            return response()->json([
                'found' => true,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * --- PWA FEATURE: Bulk Sync Offline Orders ---
     */
    public function syncOffline(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canAccessPos()) {
            return response()->json(['success' => false, 'message' => 'POS access denied. Sync blocked.'], 403);
        }

        try {
            $fallbackCounterId = $this->resolvePosCounterId($user, $request->input('counter_id'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        if ($user->isAdminUser() && ! $user->adminOpenPosSession()) {
            return response()->json(['success' => false, 'message' => 'Open an unassigned counter with opening cash before syncing sales.', 'redirect' => route('counters.sessions.open-today')], 403);
        }

        if ($user->requiresDailyOpeningBalance() && ! $user->hasOpenCounterSession()) {
            return response()->json(['success' => false, 'message' => 'Open your cash session before syncing sales.'], 403);
        }

        $shopId = $user->shop_id;
        $userId = $user->id;
        
        $orders = $request->input('orders', []);
        $syncedCount = 0;

        try {
            DB::beginTransaction();

            foreach ($orders as $offlineOrder) {
                $clientUuid = isset($offlineOrder['client_uuid'])
                    ? trim((string) $offlineOrder['client_uuid'])
                    : '';

                if ($clientUuid !== '') {
                    $existing = Order::where('shop_id', $shopId)
                        ->where('offline_client_uuid', $clientUuid)
                        ->first();
                    if ($existing) {
                        $syncedCount++;
                        continue;
                    }
                }

                // 1. Customer Handling (shared with website shoppers)
                $customerId = null;
                if (!empty($offlineOrder['customer_phone'])) {
                    $phone = Customer::normalizePhone($offlineOrder['customer_phone']);
                    $customer = Customer::where('shop_id', $shopId)->wherePhone($phone)->first();

                    if ($customer) {
                        if (!empty($offlineOrder['customer_name']) && $customer->name !== $offlineOrder['customer_name']) {
                            $customer->update(['name' => $offlineOrder['customer_name'], 'phone' => $phone]);
                        } elseif ($customer->phone !== $phone) {
                            $customer->update(['phone' => $phone]);
                        }
                        $customerId = $customer->id;
                    } else {
                        $newCustomer = Customer::create([
                            'shop_id' => $shopId,
                            'phone' => $phone,
                            'name' => $offlineOrder['customer_name'] ?? 'Guest User',
                        ]);
                        $customerId = $newCustomer->id;
                    }
                }

                $counterId = $fallbackCounterId;
                if (! empty($offlineOrder['counter_id'])) {
                    try {
                        $counterId = $this->resolvePosCounterId($user, $offlineOrder['counter_id']);
                    } catch (InvalidArgumentException $e) {
                        // Keep request-level counter.
                    }
                }

                // 2. Unique offline invoice (locked inside outer transaction)
                $invoiceNo = Order::nextPosInvoiceNo($shopId, 'OFF');

                // 3. Create Order — recompute line totals from DB prices (ignore client gross)
                $lineGross = 0.0;
                $resolvedItems = [];
                foreach (($offlineOrder['items'] ?? []) as $item) {
                    $product = Product::where('shop_id', $shopId)->find($item['id'] ?? 0);
                    if (! $product) {
                        throw new \Exception('Product not found for offline sync item.');
                    }
                    $qty = max(1, (int) ($item['qty'] ?? 0));
                    if ($product->stock_quantity < $qty) {
                        throw new \Exception("Insufficient stock for {$product->name} during offline sync.");
                    }
                    $subtotal = (float) $product->selling_price * $qty;
                    $lineGross += $subtotal;
                    $resolvedItems[] = compact('product', 'qty', 'subtotal');
                }

                if ($resolvedItems === []) {
                    throw new \Exception('Offline order has no items.');
                }

                $paid = max(0, (float) ($offlineOrder['paid_amount'] ?? $lineGross));
                $requestedDiscount = max(0, (float) ($offlineOrder['discount_amount'] ?? 0));
                $discount = min($lineGross, $requestedDiscount);
                $payable = max(0, $lineGross - $discount);
                if ($paid < $payable) {
                    $discount = min($lineGross, $lineGross - $paid);
                    $payable = $paid;
                }

                $cashPaid = array_key_exists('cash_paid', $offlineOrder) ? max(0, (float) $offlineOrder['cash_paid']) : null;
                $cardPaid = array_key_exists('card_paid', $offlineOrder) ? max(0, (float) $offlineOrder['card_paid']) : null;
                $mobilePaid = array_key_exists('mobile_paid', $offlineOrder) ? max(0, (float) $offlineOrder['mobile_paid']) : null;

                $order = Order::create([
                    'shop_id' => $shopId,
                    'user_id' => $userId,
                    'counter_id' => $counterId,
                    'customer_id' => $customerId,
                    'invoice_no' => $invoiceNo,
                    'offline_client_uuid' => $clientUuid !== '' ? $clientUuid : null,
                    'total_amount' => $lineGross,
                    'discount_amount' => $discount,
                    'paid_amount' => $paid,
                    'cash_paid' => $cashPaid,
                    'card_paid' => $cardPaid,
                    'mobile_paid' => $mobilePaid,
                    'change_amount' => max(0, $paid - $payable),
                    'payment_method' => $offlineOrder['payment_method'] ?? 'cash',
                    'status' => 'completed',
                    'created_at' => Carbon::parse($offlineOrder['created_at'] ?? now()),
                    'updated_at' => Carbon::parse($offlineOrder['created_at'] ?? now()),
                ]);

                // 4. Save Items and Deduct Stock
                foreach ($resolvedItems as $line) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $line['product']->id,
                        'quantity' => $line['qty'],
                        'unit_price' => $line['product']->selling_price,
                        'subtotal' => $line['subtotal'],
                    ]);

                    $this->stock->recordSale(
                        $line['product'],
                        (int) $line['qty'],
                        'Offline sync - '.$invoiceNo,
                        $userId,
                        'order',
                        $order->id,
                    );
                }
                $syncedCount++;

                $order->load('items.product', 'counter');
                $this->accounts->postOrderSale($order);
            }

            DB::commit();
            return response()->json(['success' => true, 'synced' => $syncedCount]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Staff use their assigned till. Admins only sell on a free till they opened themselves.
     */
    protected function resolvePosCounterId(User $user, mixed $requestedCounterId = null): int
    {
        if ($user->counter_id) {
            return (int) $user->counter_id;
        }

        if (! $user->isAdminUser()) {
            throw new InvalidArgumentException('No counter assigned to your account.');
        }

        $adminSession = $user->adminOpenPosSession();
        if (! $adminSession) {
            throw new InvalidArgumentException(
                'Select an unassigned counter and enter opening cash before selling as admin.'
            );
        }

        $counterId = (int) ($requestedCounterId ?: $adminSession->counter_id);
        if ($counterId > 0 && $counterId !== (int) $adminSession->counter_id) {
            throw new InvalidArgumentException(
                'You can only sell on the unassigned counter you opened. Close it first to switch tills.'
            );
        }

        $session = app(CounterSessionService::class)->currentOpen(
            Counter::where('shop_id', $user->shop_id)->findOrFail($adminSession->counter_id)
        );

        if (! $session || (int) $session->opened_by !== (int) $user->id) {
            throw new InvalidArgumentException(
                'You can only sell on an unassigned counter that you opened with your own opening cash.'
            );
        }

        $counter = Counter::where('shop_id', $user->shop_id)->find($adminSession->counter_id);
        if ($counter && ! $counter->isUnassigned()) {
            throw new InvalidArgumentException(
                'That counter is now assigned to staff. Close this session and use an unassigned counter.'
            );
        }

        return (int) $adminSession->counter_id;
    }

    /**
     * Resolve brand for POS filters: linked brand, brand_name text, or brand name in product title.
     *
     * @param  \Illuminate\Support\Collection<int, Brand>  $brandById
     * @param  \Illuminate\Support\Collection<int, Brand>  $brandsSortedByNameLength
     * @return array{0: int|null, 1: string|null}
     */
    protected function resolvePosBrand(Product $product, $brandById, $brandsSortedByNameLength): array
    {
        if ($product->brand_id && $brandById->has($product->brand_id)) {
            $brand = $brandById->get($product->brand_id);

            return [(int) $brand->id, $brand->name];
        }

        if ($product->brand && $product->brand->name) {
            return [(int) $product->brand->id, $product->brand->name];
        }

        $explicitName = trim((string) ($product->brand_name ?? ''));
        if ($explicitName !== '') {
            $match = $brandsSortedByNameLength->first(
                fn (Brand $b) => strcasecmp($b->name, $explicitName) === 0
            );
            if ($match) {
                return [(int) $match->id, $match->name];
            }

            return [null, $explicitName];
        }

        $productName = trim((string) $product->name);
        if ($productName !== '') {
            foreach ($brandsSortedByNameLength as $brand) {
                $brandName = trim($brand->name);
                if ($brandName === '') {
                    continue;
                }
                if (preg_match('/^'.preg_quote($brandName, '/').'(?:\b|[\s\-_])/iu', $productName)) {
                    return [(int) $brand->id, $brand->name];
                }
            }
        }

        return [null, null];
    }
}