<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Shop;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImeiSalesTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    private Counter $counter;

    private Product $phone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->shop = Shop::create([
            'name' => 'Test Shop',
            'email' => 'shop@example.com',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);
        $this->admin = User::factory()->create(['shop_id' => $this->shop->id, 'role' => 'admin']);
        $this->admin->syncRoles(['Admin']);

        $this->counter = Counter::create(['shop_id' => $this->shop->id, 'name' => 'Counter 1', 'is_active' => true]);
        CounterSession::create([
            'shop_id' => $this->shop->id,
            'counter_id' => $this->counter->id,
            'opened_by' => $this->admin->id,
            'opened_at' => now(),
            'opening_cash' => 0,
            'status' => 'open',
        ]);

        $this->phone = Product::create([
            'shop_id' => $this->shop->id,
            'name' => 'iPhone 17 Pro Max',
            'barcode' => 'IP17PM',
            'cost_price' => 180000,
            'selling_price' => 199000,
            'stock_quantity' => 0,
            'requires_imei' => true,
        ]);
        $this->phone->syncAvailableImeis([
            ['imei' => '359123000000001', 'imei2' => '359123000000091'],
            ['imei' => '359123000000002'],
            ['imei' => '359123000000003'],
        ]);
        $this->phone->update(['stock_quantity' => 3]);
    }

    private function imeiStatus(string $imei): string
    {
        return ProductImei::where('imei', $imei)->value('status');
    }

    private function checkout(array $imeis, array $extra = [])
    {
        return $this->actingAs($this->admin)->postJson(route('pos.checkout'), array_merge([
            'cart' => [['id' => $this->phone->id, 'qty' => count($imeis), 'imeis' => $imeis]],
            'payment_method' => 'cash',
            'paid_amount' => 199000 * count($imeis),
            'cash_paid' => 199000 * count($imeis),
            'counter_id' => $this->counter->id,
        ], $extra));
    }

    public function test_pos_sale_by_second_imei_sells_that_exact_phone(): void
    {
        $this->checkout(['359123000000091'])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('sold', $this->imeiStatus('359123000000001'));
        $this->assertSame('available', $this->imeiStatus('359123000000002'));
        $this->assertSame(2, (int) $this->phone->fresh()->stock_quantity);

        $item = OrderItem::where('product_id', $this->phone->id)->firstOrFail();
        $this->assertContains("IMEI 1: 359123000000001 \u{00B7} IMEI 2: 359123000000091", $item->receiptDetailLines());
    }

    public function test_pos_rejects_imei_that_is_not_in_stock(): void
    {
        $this->checkout(['359123000000099'])->assertStatus(400)->assertJson(['success' => false]);

        $this->assertSame(3, (int) $this->phone->fresh()->stock_quantity);
        $this->assertSame(0, Order::count());
    }

    public function test_refund_puts_the_sold_phone_back_in_stock(): void
    {
        $orderId = $this->checkout(['359123000000002'])->assertOk()->json('order_id');
        $this->assertSame('sold', $this->imeiStatus('359123000000002'));

        $this->post(route('sales.refund', $orderId))->assertSessionHas('success');

        $this->assertSame('available', $this->imeiStatus('359123000000002'));
        $this->assertSame(3, (int) $this->phone->fresh()->stock_quantity);
    }

    public function test_exchange_returns_the_chosen_phone_and_sells_the_new_one(): void
    {
        $orderId = $this->checkout(['359123000000001', '359123000000002'])->assertOk()->json('order_id');

        $redirect = $this->post(route('orders.exchange', $orderId), [
            'return_product_id' => $this->phone->id,
            'return_qty' => 2,
            'return_imeis' => ['359123000000002'],
        ]);
        $redirect->assertRedirect();
        $this->assertStringContainsString('return_imeis=359123000000002', urldecode($redirect->headers->get('Location')));
        $this->assertStringContainsString('return_qty=1', $redirect->headers->get('Location'));

        $this->checkout(['359123000000003'], [
            'is_exchange' => true,
            'exchange_for_order_id' => $orderId,
            'return_product_id' => $this->phone->id,
            'return_qty' => 1,
            'return_imeis' => ['359123000000002'],
            'paid_amount' => 0,
            'cash_paid' => 0,
        ])->assertOk();

        $this->assertSame('sold', $this->imeiStatus('359123000000001'));
        $this->assertSame('available', $this->imeiStatus('359123000000002'));
        $this->assertSame('sold', $this->imeiStatus('359123000000003'));
        $this->assertSame(1, (int) $this->phone->fresh()->stock_quantity);
    }

    public function test_exchange_requires_choosing_the_returned_phone(): void
    {
        $orderId = $this->checkout(['359123000000001'])->assertOk()->json('order_id');

        $this->post(route('orders.exchange', $orderId), [
            'return_product_id' => $this->phone->id,
            'return_qty' => 1,
        ])->assertSessionHas('error', 'Choose the IMEI of the phone the customer is returning.');
    }

    public function test_offline_sale_marks_its_phones_sold(): void
    {
        $this->actingAs($this->admin)->postJson(route('pos.sync'), [
            'counter_id' => $this->counter->id,
            'orders' => [[
                'client_uuid' => 'offline-1',
                'items' => [['id' => $this->phone->id, 'qty' => 1, 'imeis' => ['359123000000003']]],
                'payment_method' => 'cash',
                'paid_amount' => 199000,
                'created_at' => now()->toDateTimeString(),
            ]],
        ])->assertOk()->assertJson(['success' => true, 'synced' => 1]);

        $this->assertSame('sold', $this->imeiStatus('359123000000003'));
        $this->assertSame(2, (int) $this->phone->fresh()->stock_quantity);
    }

    private function webOrder(): Order
    {
        $order = Order::create([
            'shop_id' => $this->shop->id,
            'user_id' => $this->admin->id,
            'invoice_no' => 'WEB-'.$this->shop->id.'-2026-00001',
            'total_amount' => 199000,
            'paid_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->phone->id,
            'quantity' => 1,
            'unit_price' => 199000,
            'subtotal' => 199000,
        ]);

        return $order;
    }

    public function test_online_order_must_pick_the_phone_imei_before_packing(): void
    {
        $order = $this->webOrder();
        $item = $order->items()->first();

        $this->actingAs($this->admin)->get(route('online-orders.show', $order))
            ->assertOk()
            ->assertSee('Which phone are you sending?')
            ->assertSee('359123000000002');

        $this->post(route('online-orders.update-status', $order), ['status' => 'processing'])
            ->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, (int) $this->phone->fresh()->stock_quantity);

        $this->post(route('online-orders.update-status', $order), [
            'status' => 'processing',
            'imeis' => [$item->id => ['359123000000002']],
        ])->assertSessionHas('success');

        $this->assertSame('sold', $this->imeiStatus('359123000000002'));
        $this->assertSame($order->id, ProductImei::where('imei', '359123000000002')->value('order_id'));
        $this->assertSame(2, (int) $this->phone->fresh()->stock_quantity);

        $this->post(route('online-orders.update-status', $order), ['status' => 'cancelled'])
            ->assertSessionHas('success');
        $this->assertSame('available', $this->imeiStatus('359123000000002'));
        $this->assertSame(3, (int) $this->phone->fresh()->stock_quantity);
    }

    public function test_stock_service_rejects_web_order_without_imeis(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(StockService::class)->commitWebOrderStock($this->webOrder(), $this->admin->id);
    }

    public function test_barcode_print_gives_each_phone_its_own_imei_label(): void
    {
        $this->actingAs($this->admin)
            ->get(route('products.barcodes.print', ['product_ids' => $this->phone->id]))
            ->assertOk()
            ->assertSee('jsbarcode-value="359123000000001"', false)
            ->assertSee('jsbarcode-value="359123000000003"', false)
            ->assertSee('IMEI 2 359123000000091', false)
            ->assertDontSee('jsbarcode-value="IP17PM"', false)
            ->assertSee('3 label(s) total', false);

        $this->get(route('products.barcodes', ['q' => '359123000000091']))
            ->assertOk()
            ->assertSee('iPhone 17 Pro Max')
            ->assertSee('3 phone(s) in stock');
    }
}
