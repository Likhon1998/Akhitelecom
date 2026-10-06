<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductAndBrandManagementTest extends TestCase
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
            'name' => 'Test Shop',
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

    private function makeProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'shop_id' => $this->shop->id,
            'name' => 'Galaxy A15',
            'barcode' => 'BC-'.uniqid(),
            'cost_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
        ], $attributes));
    }

    private function sellProduct(Product $product): OrderItem
    {
        $order = Order::create([
            'shop_id' => $this->shop->id,
            'user_id' => $this->admin->id,
            'invoice_no' => 'INV-'.uniqid(),
            'total_amount' => 150,
            'paid_amount' => 150,
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
        ]);
    }

    public function test_product_without_history_is_permanently_deleted(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_sold_product_can_be_deleted_and_sales_history_is_kept(): void
    {
        $product = $this->makeProduct(['barcode' => 'SOLD-123']);
        $item = $this->sellProduct($product);

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $this->assertNull(Product::find($product->id));
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $item->refresh();
        $this->assertNotNull($item->product);
        $this->assertSame('Galaxy A15', $item->product->name);
        $this->assertContains(['label' => 'Code', 'value' => 'SOLD-123'], $item->product->receiptSpecLines());

        // Barcode is free again for a new product.
        $this->makeProduct(['barcode' => 'SOLD-123']);
        $this->assertSame(1, Product::where('barcode', 'SOLD-123')->count());
    }

    public function test_brand_can_be_created_without_logo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('brands.store'), ['name' => 'Walton', 'is_active' => 1])
            ->assertRedirect(route('brands.index'));

        $this->assertDatabaseHas('brands', ['name' => 'Walton', 'shop_id' => $this->shop->id]);
    }

    public function test_brand_can_be_created_from_product_form_quick_add(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('brands.store'), ['name' => 'Symphony', 'is_active' => true])
            ->assertOk()
            ->assertJsonPath('brand.name', 'Symphony');
    }

    /** White canvas with a coloured block, or a black block on a transparent canvas. */
    private function logoUpload(string $name, int $w, int $h, bool $transparentBlack = false): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        if ($transparentBlack) {
            imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, imagecolorallocatealpha($im, 0, 0, 0, 127));
            imagefilledrectangle($im, (int) ($w * 0.3), (int) ($h * 0.4), (int) ($w * 0.7), (int) ($h * 0.6), imagecolorallocate($im, 0, 0, 0));
        } else {
            imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, imagecolorallocate($im, 255, 255, 255));
            imagefilledrectangle($im, (int) ($w * 0.2), (int) ($h * 0.3), (int) ($w * 0.8), (int) ($h * 0.7), imagecolorallocate($im, 220, 30, 60));
        }

        $path = tempnam(sys_get_temp_dir(), 'logo');
        str_ends_with($name, '.png') ? imagepng($im, $path) : imagejpeg($im, $path, 85);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_brand_can_be_created_with_large_photo_logo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('brands.store'), [
                'name' => 'Realme',
                'is_active' => 1,
                'logo' => $this->logoUpload('realme.jpg', 4000, 3000),
            ])
            ->assertRedirect(route('brands.index'));

        $brand = Brand::where('name', 'Realme')->firstOrFail();
        $this->assertStringEndsWith('.png', $brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);

        [$w, $h] = getimagesize(Storage::disk('public')->path($brand->logo_path));
        $this->assertLessThanOrEqual(640, max($w, $h));
    }

    public function test_black_logo_on_transparent_background_is_kept(): void
    {
        $this->actingAs($this->admin)
            ->post(route('brands.store'), [
                'name' => 'Sony',
                'is_active' => 1,
                'logo' => $this->logoUpload('sony.png', 400, 200, transparentBlack: true),
            ])
            ->assertRedirect(route('brands.index'));

        $brand = Brand::where('name', 'Sony')->firstOrFail();
        Storage::disk('public')->assertExists($brand->logo_path);

        $out = imagecreatefrompng(Storage::disk('public')->path($brand->logo_path));
        $center = imagecolorat($out, intdiv(imagesx($out), 2), intdiv(imagesy($out), 2));
        $this->assertSame(0, ($center >> 24) & 0x7F, 'Black logo pixels must stay opaque.');
        $this->assertLessThan(400, imagesx($out), 'Transparent padding should be cropped.');
    }

    public function test_brand_logo_that_cannot_be_processed_still_saves_brand(): void
    {
        // A solid black image has no "content" pixels for the auto-cropper.
        $this->actingAs($this->admin)
            ->post(route('brands.store'), [
                'name' => 'Itel',
                'is_active' => 1,
                'logo' => UploadedFile::fake()->image('black.png', 200, 200),
            ])
            ->assertRedirect(route('brands.index'));

        $brand = Brand::where('name', 'Itel')->firstOrFail();
        $this->assertNotEmpty($brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);
    }

    public function test_brand_logo_can_be_replaced(): void
    {
        $brand = Brand::create(['shop_id' => $this->shop->id, 'name' => 'Vivo', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->put(route('brands.update', $brand), [
                'name' => 'Vivo',
                'is_active' => 1,
                'logo' => $this->logoUpload('vivo.png', 300, 150),
            ])
            ->assertRedirect(route('brands.index'));

        $brand->refresh();
        $this->assertMatchesRegularExpression('#^brands/[0-9a-f-]{36}\.png$#', $brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);
    }

    public function test_stock_can_be_returned_for_deleted_product(): void
    {
        $product = $this->makeProduct();
        $this->sellProduct($product);
        $product->archive();

        $this->actingAs($this->admin);
        app(\App\Services\StockService::class)->apply($product, 'in', 1, 'REFUND-1', 'return');

        $this->assertSame(6, Product::withTrashed()->find($product->id)->stock_quantity);
    }

    private function createGadgetWithSharedPhotos(): array
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'product_mode' => 'gadget',
                'name' => 'Redmi Note 13',
                'cost_price' => 18000,
                'selling_price' => 21000,
                'variants' => [
                    ['barcode' => 'RN13-BLK', 'color' => 'Black', 'stock_quantity' => ''],
                    ['barcode' => 'RN13-BLU', 'color' => 'Blue', 'stock_quantity' => ''],
                ],
                'images' => [
                    UploadedFile::fake()->image('front.jpg', 600, 600),
                    UploadedFile::fake()->image('back.jpg', 600, 600),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        return [
            Product::where('barcode', 'RN13-BLK')->firstOrFail(),
            Product::where('barcode', 'RN13-BLU')->firstOrFail(),
        ];
    }

    public function test_simple_product_is_created_with_pictures(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'product_mode' => 'simple',
                'name' => 'Anker Charger',
                'barcode' => 'ANK-20W',
                'cost_price' => 900,
                'selling_price' => 1200,
                'stock_quantity' => 3,
                'images' => [
                    UploadedFile::fake()->image('a.jpg', 500, 500),
                    UploadedFile::fake()->image('b.png', 500, 500),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $product = Product::where('barcode', 'ANK-20W')->firstOrFail();
        $paths = $product->imagePaths();

        $this->assertCount(2, $paths);
        $this->assertSame($paths[0], $product->image);
        $this->assertSame(3, (int) $product->stock_quantity);
        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->get(route('products.index'))->assertOk()->assertSee('Anker Charger');
        $this->get(route('products.edit', $product))->assertOk();
    }

    public function test_deleting_one_variant_keeps_sibling_pictures(): void
    {
        [$black, $blue] = $this->createGadgetWithSharedPhotos();
        $shared = $blue->imagePaths();
        $this->assertSame($black->imagePaths(), $shared);

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $black))
            ->assertRedirect(route('products.index'));

        foreach ($shared as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $blue))
            ->assertRedirect(route('products.index'));

        foreach ($shared as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_removing_picture_from_one_variant_keeps_it_for_siblings(): void
    {
        [$black, $blue] = $this->createGadgetWithSharedPhotos();
        $firstImage = $black->galleryImages()->first();

        $this->actingAs($this->admin)
            ->put(route('products.update', $black), [
                'name' => $black->name,
                'barcode' => $black->barcode,
                'cost_price' => 18000,
                'selling_price' => 21000,
                'is_published' => 1,
                'remove_images' => [$firstImage->id],
                'images' => [UploadedFile::fake()->image('new.jpg', 400, 400)],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $black->refresh();
        $this->assertCount(2, $black->imagePaths());
        $this->assertNotContains($firstImage->path, $black->imagePaths());
        $this->assertSame($black->imagePaths()[0], $black->image);

        Storage::disk('public')->assertExists($firstImage->path);
        $this->assertContains($firstImage->path, $blue->fresh()->imagePaths());
    }

    public function test_removing_unshared_picture_deletes_the_file(): void
    {
        $product = $this->makeProduct();
        $path = UploadedFile::fake()->image('solo.jpg')->store('products', 'public');
        $image = $product->galleryImages()->create(['path' => $path, 'sort_order' => 0]);
        $product->forceFill(['image' => $path])->save();

        $this->actingAs($this->admin)
            ->put(route('products.update', $product), [
                'name' => $product->name,
                'barcode' => $product->barcode,
                'cost_price' => 100,
                'selling_price' => 150,
                'remove_images' => [$image->id],
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($product->fresh()->image);
    }

    public function test_product_with_stock_history_is_archived_not_erased(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'product_mode' => 'simple',
                'name' => 'JBL Go 3',
                'barcode' => 'JBL-GO3',
                'cost_price' => 3000,
                'selling_price' => 3800,
                'stock_quantity' => 4,
            ])
            ->assertSessionHasNoErrors();

        $product = Product::where('barcode', 'JBL-GO3')->firstOrFail();

        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id]);
        $this->get(route('products.index'))->assertOk()->assertDontSee('JBL Go 3');
    }

    public function test_brand_with_missing_logo_file_still_shows_a_logo(): void
    {
        $brand = Brand::create([
            'shop_id' => $this->shop->id,
            'name' => 'Oppo',
            'logo_path' => 'brands/missing-file.png',
            'is_active' => true,
        ]);

        $this->assertStringContainsString('/storage/brands/oppo.svg', $brand->logo_url);

        $this->actingAs($this->admin)
            ->get(route('brands.index'))
            ->assertOk()
            ->assertSee('/storage/brands/oppo.svg', false)
            ->assertDontSee('missing-file.png', false);
    }

    public function test_deleting_brand_keeps_logo_used_by_another_brand(): void
    {
        $path = UploadedFile::fake()->image('logo.png')->store('brands', 'public');
        $keep = Brand::create(['shop_id' => $this->shop->id, 'name' => 'Tecno', 'logo_path' => $path, 'is_active' => true]);
        $dup = Brand::create(['shop_id' => $this->shop->id, 'name' => 'Tecno Mobile', 'logo_path' => $path, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->delete(route('brands.destroy', $dup))
            ->assertRedirect(route('brands.index'));

        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($keep->fresh());
    }

    public function test_storefront_product_page_shows_pictures_and_brand_logo(): void
    {
        $logo = UploadedFile::fake()->image('xiaomi.png')->store('brands', 'public');
        $brand = Brand::create(['shop_id' => $this->shop->id, 'name' => 'Xiaomi', 'logo_path' => $logo, 'is_active' => true]);
        $product = $this->makeProduct(['name' => 'Xiaomi Buds', 'brand_id' => $brand->id, 'brand_name' => 'Xiaomi', 'is_published' => true]);
        $photo = UploadedFile::fake()->image('buds.jpg')->store('products', 'public');
        $product->galleryImages()->create(['path' => $photo, 'sort_order' => 0]);
        $product->forceFill(['image' => $photo])->save();

        $this->get(route('website.product', $product))
            ->assertOk()
            ->assertSee(basename($photo), false)
            ->assertSee('/storage/'.$logo, false);
    }

    private function imeiUpdate(Product $product, array $imeis, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->put(route('products.update', $product), array_merge([
            'name' => $product->name,
            'barcode' => $product->barcode,
            'cost_price' => 20000,
            'selling_price' => 24000,
            'is_published' => 1,
            'requires_imei' => 1,
            'imei_list' => implode("\n", $imeis),
        ], $extra));
    }

    public function test_five_identical_phones_with_separate_imeis(): void
    {
        $imeis = ['356938035643801', '356938035643802', '356938035643803', '356938035643804', '356938035643805'];

        $this->actingAs($this->admin)
            ->post(route('products.store'), [
                'product_mode' => 'simple',
                'name' => 'Galaxy A15 — Black / 8GB / 128GB',
                'barcode' => 'A15-BLK-128',
                'color' => 'Black',
                'ram' => '8GB',
                'storage' => '128GB',
                'cost_price' => 20000,
                'selling_price' => 24000,
                'requires_imei' => 1,
                'imei_list' => implode("\n", $imeis),
            ])
            ->assertSessionHasNoErrors();

        $product = Product::where('barcode', 'A15-BLK-128')->firstOrFail();
        $this->assertSame(5, (int) $product->stock_quantity);
        $this->assertSame(5, $product->availableImeis()->count());

        // 3 more arrive later: stock follows the IMEI list.
        $more = array_merge($imeis, ['356938035643806', '356938035643807', '356938035643808']);
        $this->imeiUpdate($product, $more)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Product updated. Stock set to 8 to match the available IMEI list.');
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'in', 'quantity' => 3, 'reason' => 'adjustment']);
        $this->assertDatabaseHas('account_transactions', ['type' => 'stock_adjustment']);

        // One IMEI removed (entered by mistake).
        $this->imeiUpdate($product, array_slice($more, 0, 7))->assertSessionHasNoErrors();
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);

        // Editing other fields without touching IMEIs leaves stock alone.
        $this->imeiUpdate($product, array_slice($more, 0, 7), ['selling_price' => 23500])->assertSessionHasNoErrors();
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    public function test_imeis_added_later_to_product_without_stock_record_opening_inventory(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 0, 'requires_imei' => true]);

        $this->imeiUpdate($product, ['111111111111111', '222222222222222'])->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'reason' => 'opening_inventory', 'quantity' => 2]);
    }

    public function test_duplicate_imei_on_another_product_is_rejected(): void
    {
        $a = $this->makeProduct(['stock_quantity' => 0, 'requires_imei' => true]);
        $b = $this->makeProduct(['stock_quantity' => 0, 'requires_imei' => true]);
        $this->imeiUpdate($a, ['999999999999999'])->assertSessionHasNoErrors();

        $this->imeiUpdate($b, ['999999999999999'])->assertSessionHasErrors('imei_list');

        $this->assertSame(0, (int) $b->fresh()->stock_quantity);
        $this->assertSame(1, (int) $a->fresh()->stock_quantity);

        $this->post(route('products.store'), [
            'product_mode' => 'simple',
            'name' => 'Dup phone',
            'barcode' => 'DUP-1',
            'cost_price' => 1,
            'selling_price' => 2,
            'requires_imei' => 1,
            'imei_list' => '999999999999999',
        ])->assertSessionHasErrors('imei_list');
        $this->assertDatabaseMissing('products', ['barcode' => 'DUP-1']);
    }

    public function test_brands_page_renders(): void
    {
        Brand::create(['shop_id' => $this->shop->id, 'name' => 'Nokia', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->get(route('brands.index'))
            ->assertOk()
            ->assertSee('Nokia');
    }
}
