<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceNumberEncodingTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create([
            'name' => 'Akhi Telecom',
            'email' => 'shop@example.com',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'shop_id' => $this->shop->id,
            'role' => 'admin',
        ]);
    }

    public function test_pos_invoice_encodes_day_month_year_shop_and_daily_position(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-17 14:30:00', 'Asia/Dhaka'));

        $first = null;
        $second = null;

        DB::transaction(function () use (&$first, &$second) {
            $first = Order::nextPosInvoiceNo($this->shop->id, 'INV');
            Order::create([
                'shop_id' => $this->shop->id,
                'user_id' => $this->cashier->id,
                'invoice_no' => $first,
                'total_amount' => 100,
                'paid_amount' => 100,
                'status' => 'completed',
                'payment_method' => 'cash',
            ]);

            $second = Order::nextPosInvoiceNo($this->shop->id, 'INV');
        });

        // 17 = day, K = November, 26 = year, 01 = shop, ##### = daily position
        $this->assertSame('INV-17K2601-00001', $first);
        $this->assertSame('INV-17K2601-00002', $second);

        $decoded = Order::decodeInvoiceNo($first);
        $this->assertNotNull($decoded);
        $this->assertSame('INV', $decoded['kind']);
        $this->assertSame(17, $decoded['day']);
        $this->assertSame(11, $decoded['month']);
        $this->assertSame('K', $decoded['month_letter']);
        $this->assertSame(2026, $decoded['year']);
        $this->assertSame(1, $decoded['shop_id']);
        $this->assertSame(1, $decoded['position']);

        Carbon::setTestNow();
    }

    public function test_web_invoice_uses_same_encoding_with_web_prefix(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 09:00:00', 'Asia/Dhaka'));

        $invoice = null;
        DB::transaction(function () use (&$invoice) {
            $invoice = Order::nextWebInvoiceNo($this->shop->id);
        });

        // 05 = day, A = January, 26 = year, 01 = shop
        $this->assertSame('WEB-05A2601-00001', $invoice);
        $this->assertTrue(str_starts_with($invoice, 'WEB-'));
        $this->assertSame(1, Order::decodeInvoiceNo($invoice)['month']);

        Carbon::setTestNow();
    }

    public function test_decode_returns_null_for_legacy_invoice_numbers(): void
    {
        $this->assertNull(Order::decodeInvoiceNo('INV-1-2026-00265'));
        $this->assertNull(Order::decodeInvoiceNo('WEB-1-2026-00001'));
    }

    public function test_daily_sequence_resets_on_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-17 23:00:00', 'Asia/Dhaka'));

        DB::transaction(function () {
            $today = Order::nextPosInvoiceNo($this->shop->id, 'INV');
            Order::create([
                'shop_id' => $this->shop->id,
                'user_id' => $this->cashier->id,
                'invoice_no' => $today,
                'total_amount' => 50,
                'paid_amount' => 50,
                'status' => 'completed',
                'payment_method' => 'cash',
            ]);
            $this->assertSame('INV-17K2601-00001', $today);
        });

        Carbon::setTestNow(Carbon::parse('2026-11-18 09:00:00', 'Asia/Dhaka'));

        DB::transaction(function () {
            $nextDay = Order::nextPosInvoiceNo($this->shop->id, 'INV');
            $this->assertSame('INV-18K2601-00001', $nextDay);
        });

        Carbon::setTestNow();
    }
}
