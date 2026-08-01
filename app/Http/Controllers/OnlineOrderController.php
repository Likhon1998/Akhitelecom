<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AccountService;
use App\Services\OnlineOrderTrackingService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OnlineOrderController extends Controller
{
    public function __construct(
        protected AccountService $accounts,
        protected StockService $stock,
        protected OnlineOrderTrackingService $tracking,
    ) {}

    protected function ensureAdmin(): void
    {
        if (! Auth::user()?->isAdminUser()) {
            abort(403, 'Online orders are only available to shop admins.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();
        $shopId = Auth::user()->shop_id;

        $filterDate = $request->input('date');
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'all') ?: 'all';

        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        $statsQuery = Order::where('shop_id', $shopId)->onlineOrders();
        if ($filterDate) {
            $statsQuery->whereDate('created_at', $filterDate);
        }

        $stats = $statsQuery->selectRaw("
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_count,
            COALESCE(SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END), 0) as shipped_count,
            COALESCE(SUM(CASE WHEN status = 'shipped' AND payment_method = 'cash_on_delivery' THEN total_amount - COALESCE(delivery_charge, 0) ELSE 0 END), 0) as courier_receivables,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN paid_amount - COALESCE(delivery_charge, 0) ELSE 0 END), 0) as settled_revenue
        ")->first();

        $pendingCount = (int) ($stats->pending_count ?? 0);
        $processingCount = (int) ($stats->processing_count ?? 0);
        $shippedCount = (int) ($stats->shipped_count ?? 0);
        $courierReceivables = (float) ($stats->courier_receivables ?? 0);
        $settledRevenue = (float) ($stats->settled_revenue ?? 0);

        // Preload recent orders for instant client-side filtering (no page reload).
        $liveQuery = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->with([
                'customer:id,name,phone,address',
                'items:id,order_id,product_id,quantity,subtotal',
                'items.product:id,name',
            ]);

        if ($filterDate) {
            $liveQuery->whereDate('created_at', $filterDate);
        }

        $ordersPayload = $liveQuery->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (Order $order) => $this->orderListPayload($order))
            ->values();

        return view('online-orders.index', compact(
            'ordersPayload',
            'pendingCount',
            'processingCount',
            'shippedCount',
            'courierReceivables',
            'settledRevenue',
            'filterDate',
            'search',
            'statusFilter',
        ));
    }

    private function orderListPayload(Order $order): array
    {
        $productRevenue = (float) $order->total_amount - (float) ($order->delivery_charge ?? 0);
        $isVoided = in_array($order->status, ['refunded', 'cancelled', 'returned'], true);

        return [
            'id' => $order->id,
            'invoice' => $order->invoice_no,
            'status' => $order->status,
            'created_at' => asian_datetime($order->created_at, 'd M Y, h:i A'),
            'payment_method' => str_replace('_', ' ', (string) $order->payment_method),
            'product_revenue' => number_format($productRevenue, 2),
            'delivery_charge' => (float) ($order->delivery_charge ?? 0),
            'delivery_charge_fmt' => number_format((float) ($order->delivery_charge ?? 0), 2),
            'shipping_courier' => $order->shipping_courier,
            'shipping_tracking_no' => $order->shipping_tracking_no,
            'is_voided' => $isVoided,
            'show_url' => route('online-orders.show', $order),
            'receipt_url' => route('pos.receipt', $order->id),
            'customer_name' => $order->customer->name ?? 'Guest',
            'customer_phone' => $order->customer->phone ?? 'N/A',
            'customer_address' => $order->customer->address ?? 'No address provided',
            'items' => $order->items->map(fn ($item) => [
                'qty' => (int) $item->quantity,
                'name' => $item->product->name ?? 'Unknown Product',
            ])->values()->all(),
            'search_blob' => mb_strtolower(implode(' ', array_filter([
                $order->invoice_no,
                $order->shipping_tracking_no,
                $order->customer?->name,
                $order->customer?->phone,
            ]))),
        ];
    }

    public function show(Order $order)
    {
        $this->ensureAdmin();
        abort_unless($order->shop_id === Auth::user()->shop_id && $order->isOnlineOrder(), 403);

        // Opening an order from the bell should clear the unread badge.
        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        $order->load([
            'customer:id,name,phone,address,email',
            'items:id,order_id,product_id,quantity,unit_price,subtotal',
            'items.product:id,name',
            'statusLogs' => fn ($q) => $q->latest('id')->limit(20),
        ]);

        $timeline = $this->tracking->customerTimeline($order);
        $statusLabels = $this->tracking->statusLabels();
        $allowedNextStatuses = array_values(array_unique(array_merge(
            [$order->status],
            self::STATUS_TRANSITIONS[$order->status] ?? [],
        )));

        return view('online-orders.show', compact('order', 'timeline', 'statusLabels', 'allowedNextStatuses'));
    }

    public function notifications()
    {
        $this->ensureAdmin();
        $shopId = Auth::user()->shop_id;
        $seenAt = session('online_orders_seen_at');

        $unreadQuery = Order::where('shop_id', $shopId)->onlineOrders();
        if ($seenAt) {
            $unreadQuery->where('created_at', '>', $seenAt);
        } else {
            // First visit: only treat the last 24 hours as unread.
            $unreadQuery->where('created_at', '>', now()->subDay());
        }
        $unread = (int) $unreadQuery->count();

        $orders = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->with('customer:id,name,phone')
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'invoice_no', 'status', 'total_amount', 'customer_id', 'created_at']);

        $labels = $this->tracking->statusLabels();

        $items = $orders->map(function (Order $order) use ($seenAt, $labels) {
            $isNew = $seenAt
                ? $order->created_at->greaterThan($seenAt)
                : $order->created_at->greaterThan(now()->subDay());

            return [
                'id' => $order->id,
                'invoice' => $order->invoice_no,
                'status' => $order->status,
                'status_label' => $labels[$order->status] ?? ucfirst($order->status),
                'customer' => $order->customer?->name ?? 'Guest',
                'phone' => $order->customer?->phone,
                'total' => number_format((float) $order->total_amount, 2),
                'at' => $order->created_at->diffForHumans(),
                'url' => route('online-orders.show', $order),
                'is_new' => $isNew,
            ];
        });

        return response()->json([
            'unread' => $unread,
            'items' => $items->values(),
        ]);
    }

    public function markNotificationsSeen()
    {
        $this->ensureAdmin();
        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        return response()->json(['ok' => true]);
    }

    /** Allowed forward status changes for online orders (terminal states cannot reopen). */
    private const STATUS_TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'returned', 'cancelled'],
        'completed' => ['refunded'],
        'cancelled' => [],
        'returned' => [],
        'refunded' => [],
    ];

    public function updateStatus(Request $request, Order $order)
    {
        $this->ensureAdmin();
        if ($order->shop_id !== Auth::user()->shop_id || ! $order->isOnlineOrder()) {
            abort(403, 'Unauthorized Access');
        }

        $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled,returned,refunded',
            'customer_note' => 'nullable|string|max:500',
            'courier_name' => 'nullable|string|max:120',
            'tracking_number' => 'nullable|string|max:120',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        if (
            $oldStatus === $newStatus
            && ! $request->filled('customer_note')
            && ! $request->filled('tracking_number')
            && ! $request->filled('courier_name')
        ) {
            return back();
        }

        if ($oldStatus !== $newStatus) {
            $allowed = self::STATUS_TRANSITIONS[$oldStatus] ?? [];
            if (! in_array($newStatus, $allowed, true)) {
                return back()->with('error', "Cannot change status from {$oldStatus} to {$newStatus}.");
            }
        }

        if ($newStatus === 'shipped' && ! $request->filled('courier_name') && blank($order->shipping_courier)) {
            return back()->with('error', 'Please enter a courier / delivery partner name when marking as shipped.');
        }

        $isCod = in_array(strtolower((string) $order->payment_method), ['cash_on_delivery', 'cod', 'cash on delivery'], true);
        $moneyCollected = $isCod
            ? ($oldStatus === 'completed' || (float) $order->paid_amount > 0)
            : ((float) $order->paid_amount > 0 || $oldStatus === 'completed');

        if ($newStatus === 'refunded' && $oldStatus !== 'completed') {
            return back()->with('error', 'Refund is only available after delivery. Use Returned if the order is not delivered yet.');
        }

        if ($newStatus === 'returned' && $oldStatus === 'completed') {
            return back()->with('error', 'This order is already delivered. Use Refund instead of Returned.');
        }

        try {
            DB::beginTransaction();

            $paidAmount = $order->paid_amount;

            if ($newStatus === 'completed' && $order->payment_method === 'cash_on_delivery') {
                $paidAmount = $order->total_amount;
            }

            if (in_array($newStatus, ['cancelled', 'returned', 'refunded'])) {
                $paidAmount = 0;
            }

            $courierName = $request->filled('courier_name')
                ? $request->courier_name
                : $order->shipping_courier;
            $trackingNumber = $request->filled('tracking_number')
                ? $request->tracking_number
                : $order->shipping_tracking_no;

            $order->update([
                'status' => $newStatus,
                'paid_amount' => $paidAmount,
                'shipping_courier' => $courierName,
                'shipping_tracking_no' => $trackingNumber,
            ]);

            $defaultNotes = [
                'processing' => 'We are packing your items now.',
                'shipped' => 'Your package is on the way to your delivery address.',
                'completed' => 'Order delivered successfully.',
                'cancelled' => 'This order was cancelled.',
                'returned' => $isCod && ! $moneyCollected
                    ? 'Order returned. COD was not collected — no customer refund.'
                    : 'This order was returned to our store.',
                'refunded' => 'This order was refunded.',
            ];

            $this->tracking->upsertLatestLog(
                $order,
                $newStatus,
                $request->customer_note ?: ($defaultNotes[$newStatus] ?? null),
                $courierName,
                $trackingNumber,
                Auth::id(),
            );

            if ($newStatus === 'processing' && $oldStatus === 'pending') {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, Auth::id());
            }

            // If an order skips packing and goes pending → shipped, still commit stock.
            if ($newStatus === 'shipped' && in_array($oldStatus, ['pending', 'processing'], true)) {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, Auth::id());
            }

            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, Auth::id());
                $this->accounts->postWebSettlement($order);
            }

            if (in_array($newStatus, ['cancelled', 'returned', 'refunded']) && ! in_array($oldStatus, ['cancelled', 'returned', 'refunded'])) {
                foreach ($order->items as $item) {
                    $product = $item->product;

                    if ($product) {
                        $this->stock->restockForDocument(
                            $product,
                            $item->quantity,
                            'Order '.ucfirst($newStatus).' - '.$order->invoice_no,
                            'order_refund',
                            $order->id,
                            'order_'.$newStatus,
                            Auth::id(),
                        );
                    }
                }

                $order->load('items.product', 'counter');
                $this->accounts->postOrderRefund($order);
            }

            DB::commit();

            return back()->with('success', "Order {$order->invoice_no} updated to ".ucfirst($newStatus).'. Customer can now see this on tracking.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }
}
