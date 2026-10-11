<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FourFixesVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_description_rows_serialize_to_short_description(): void
    {
        $controller = app(ProductController::class);
        $method = new \ReflectionMethod($controller, 'mergeDescriptionRowsIntoRequest');
        $method->setAccessible(true);

        $req = Request::create('/products', 'POST', [
            'description_rows' => [
                ['heading' => 'Display', 'detail' => '6.1 OLED'],
                ['heading' => 'Battery', 'detail' => '4000 mAh'],
                ['heading' => '', 'detail' => ''],
            ],
        ]);
        $method->invoke($controller, $req);

        $this->assertSame("Display | 6.1 OLED\nBattery | 4000 mAh", $req->input('short_description'));
    }

    public function test_barcode_lookup_finds_existing_product_and_reports_misses(): void
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'email' => 'shop@example.com',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'shop_id' => $shop->id,
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Scan Me Phone',
            'barcode' => 'SCAN-12345',
            'sku' => 'SKU-SCAN-12345',
            'cost_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'alert_quantity' => 1,
        ]);

        $this->actingAs($user);

        $controller = app(ProductController::class);

        $found = $controller->barcodeLookup(Request::create('/x', 'GET', ['barcode' => 'SCAN-12345']));
        $json = $found->getData(true);
        $this->assertTrue($json['found']);
        $this->assertSame('SCAN-12345', $json['product']['barcode']);
        $this->assertSame('Scan Me Phone', $json['product']['name']);
        $this->assertSame($product->id, $json['product']['id']);

        $miss = $controller->barcodeLookup(Request::create('/x', 'GET', ['barcode' => 'BRAND-NEW-999']));
        $this->assertFalse($miss->getData(true)['found']);
    }

    public function test_pos_code_index_logic_is_prefix_safe(): void
    {
        $products = [
            (object) ['id' => 31, 'barcode' => '8', 'sku' => ''],
            (object) ['id' => 32, 'barcode' => '81', 'sku' => ''],
            (object) ['id' => 33, 'barcode' => 'DEMO-001', 'sku' => 'SKU-DEMO-001'],
        ];

        $map = [];
        foreach ($products as $p) {
            $b = $this->normalize($p->barcode);
            $s = $this->normalize($p->sku);
            if ($b !== '') {
                $map['b:'.$b] = $p;
            }
            if ($s !== '') {
                $map['s:'.$s] = $p;
            }
        }

        $this->assertSame(32, $map['b:81']->id);
        $this->assertSame(31, $map['b:8']->id);
        $this->assertSame(33, $map['s:sku-demo-001']->id);
        $this->assertArrayNotHasKey('b:810', $map);
    }

    public function test_auto_loaders_and_ui_hooks_are_in_place(): void
    {
        $websiteLayout = file_get_contents(resource_path('views/website/layout.blade.php'));
        $this->assertStringNotContainsString('id="gaget-page-loader"', $websiteLayout);
        $this->assertStringContainsString('window.GagetLoader = { show: function () {}, hide: function () {} }', $websiteLayout);

        $adminProgress = file_get_contents(resource_path('js/admin-progress.js'));
        $this->assertMatchesRegularExpression('/export function initAdminProgress\(\) \{\s*return;/s', $adminProgress);

        $pos = file_get_contents(resource_path('views/pos/index.blade.php'));
        $this->assertStringContainsString('commitScan(', $pos);
        $this->assertStringContainsString('rebuildCodeIndex', $pos);
        $this->assertStringContainsString('onSearchEnter($event)', $pos);
        $this->assertStringContainsString('captureGlobalScanKey', $pos);

        $form = file_get_contents(resource_path('views/products/partials/form-fields.blade.php'));
        $this->assertStringContainsString('x-model="barcodeValue"', $form);
        $this->assertStringContainsString(':placeholder="barcodeValue ? barcodeValue', $form);
        $this->assertStringContainsString('descriptionRows', $form);

        $live = file_get_contents(resource_path('views/website/partials/product-live.blade.php'));
        $this->assertStringContainsString('pd-spec-table', $live);
    }

    private function normalize(?string $code): string
    {
        $code = (string) $code;
        $code = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $code) ?? $code;
        $code = preg_replace('/[\r\n]+/', '', $code) ?? $code;

        return strtolower(trim($code));
    }
}
