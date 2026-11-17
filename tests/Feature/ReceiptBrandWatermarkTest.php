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

    private Shop $shop;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->shop = Shop::create([
            'name' => 'Akhi Telecom',
            'email' => 'shop@example.com',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'shop_id' => $this->shop->id,
            'role' => 'admin',
        ]);
        $this->admin->syncRoles(['Admin']);
    }

    private function makeOrder(): Order
    {
        $product = Product::create([
            'shop_id' => $this->shop->id,
            'name' => 'Test Phone',
            'barcode' => 'WM-TEST-'.uniqid(),
            'cost_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
        ]);

        $order = Order::create([
            'shop_id' => $this->shop->id,
            'user_id' => $this->admin->id,
            'invoice_no' => 'INV-WM-'.uniqid(),
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

        return $order;
    }

    public function test_pos_receipt_embeds_homepage_logo_for_old_and_new_invoices(): void
    {
        $logo = UploadedFile::fake()->image('brand-logo.png', 200, 200)->store('cms/logo', 'public');
        SiteSetting::query()->updateOrCreate([], [
            'default_shop_id' => $this->shop->id,
            'store_name' => 'Akhi Telecom',
            'logo_path' => $logo,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $src = SiteSetting::current()->storeLogoSrc();
        $this->assertNotNull($src);
        $this->assertStringStartsWith('data:image/', $src);

        $order = $this->makeOrder();

        $this->actingAs($this->admin, 'admin')
            ->get(route('pos.receipt', $order))
            ->assertOk()
            ->assertSee('class="logo-watermark"', false)
            ->assertSee('class="brand-logo"', false)
            ->assertSee('Sales Invoice / POS Receipt', false)
            ->assertSee('data:image/', false);
    }

    public function test_receipt_falls_back_to_homepage_favicon_when_full_logo_missing(): void
    {
        $favicon = UploadedFile::fake()->image('favicon.png', 64, 64)->store('cms/favicon', 'public');
        SiteSetting::query()->updateOrCreate([], [
            'default_shop_id' => $this->shop->id,
            'store_name' => 'Akhi Telecom',
            'logo_path' => null,
            'favicon_path' => $favicon,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $this->assertSame($favicon, SiteSetting::current()->storeBrandImagePath());
        $this->assertStringStartsWith('data:image/', (string) SiteSetting::current()->storeLogoSrc());

        $order = $this->makeOrder();

        $this->actingAs($this->admin, 'admin')
            ->get(route('pos.receipt', $order))
            ->assertOk()
            ->assertSee('class="logo-watermark"', false)
            ->assertSee('data:image/', false);
    }
}
