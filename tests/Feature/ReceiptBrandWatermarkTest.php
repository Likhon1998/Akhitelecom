<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptBrandWatermarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_receipt_and_invoice_show_brand_logo_watermark(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Akhi Telecom',
            'email' => 'shop@example.com',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);
        $admin->syncRoles(['Admin']);

        $logo = UploadedFile::fake()->image('brand-logo.png', 200, 200)->store('site', 'public');
        SiteSetting::query()->updateOrCreate([], [
            'default_shop_id' => $shop->id,
            'store_name' => 'Akhi Telecom',
            'logo_path' => $logo,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Test Phone',
            'barcode' => 'WM-TEST-1',
            'cost_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
        ]);

        $order = Order::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'invoice_no' => 'INV-WM-1',
            'total_amount' => 150,
            'paid_amount' => 150,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('pos.receipt', $order))
            ->assertOk()
            ->assertSee('class="logo-watermark"', false)
            ->assertSee('class="brand-logo"', false)
            ->assertSee('Sales Invoice / POS Receipt', false)
            ->assertSee('/storage/'.$logo, false);
    }
}
