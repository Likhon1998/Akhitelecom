<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpandProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_simple_product_can_convert_to_variant_family(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Variant Shop',
            'email' => 'variant-shop@example.com',
            'phone' => '01700000001',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $user->syncRoles(['Admin']);

        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Phones', 'slug' => 'phones']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Xiaomi', 'is_active' => true, 'sort_order' => 0]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Redmi Note 17',
            'barcode' => 'RN17-BLACK-128',
            'color' => 'Black',
            'color_hex' => '#111111',
            'storage' => '128GB',
            'ram' => '8GB',
            'cost_price' => 20000,
            'selling_price' => 25000,
            'stock_quantity' => 3,
            'alert_quantity' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user, 'admin');

        $response = $this->put(route('products.update', $product), [
            'product_mode' => 'gadget',
            'name' => 'Redmi Note 17',
            'variant_group' => 'redmi-note-17',
            'cost_price' => 20000,
            'selling_price' => 25000,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_published' => 1,
            'variants' => [
                [
                    'id' => $product->id,
                    'barcode' => 'RN17-BLACK-128',
                    'color' => 'Black',
                    'color_hex' => '#111111',
                    'storage' => '128GB',
                    'ram' => '8GB',
                    'cost_price' => 20000,
                    'selling_price' => 25000,
                ],
                [
                    'barcode' => 'RN17-BLUE-256',
                    'color' => 'Blue',
                    'color_hex' => '#2563eb',
                    'storage' => '256GB',
                    'ram' => '8GB',
                    'cost_price' => 21000,
                    'selling_price' => 27000,
                    'stock_quantity' => 2,
                ],
            ],
        ]);

        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame('redmi-note-17', $product->variant_group);
        $this->assertSame('Black', $product->color);

        $sibling = Product::query()->where('barcode', 'RN17-BLUE-256')->first();
        $this->assertNotNull($sibling);
        $this->assertSame('redmi-note-17', $sibling->variant_group);
        $this->assertSame('Redmi Note 17', $sibling->name);
        $this->assertSame('Blue', $sibling->color);
        $this->assertSame('256 GB', $sibling->storage);
        $this->assertSame($shop->id, $sibling->shop_id);
        $this->assertSame($brand->id, $sibling->brand_id);
        $this->assertEquals(2, (int) $sibling->stock_quantity);
    }

    public function test_variant_family_edit_updates_siblings_and_can_remove_one(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Family Shop',
            'email' => 'family-shop@example.com',
            'phone' => '01700000002',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $user->syncRoles(['Admin']);

        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Phones', 'slug' => 'phones-2']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Samsung', 'is_active' => true, 'sort_order' => 0]);

        $black = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Galaxy A16',
            'barcode' => 'A16-BLACK',
            'variant_group' => 'galaxy-a16',
            'color' => 'Black',
            'cost_price' => 15000,
            'selling_price' => 18000,
            'stock_quantity' => 1,
            'alert_quantity' => 1,
            'is_published' => true,
        ]);
        $blue = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Galaxy A16',
            'barcode' => 'A16-BLUE',
            'variant_group' => 'galaxy-a16',
            'color' => 'Blue',
            'cost_price' => 15000,
            'selling_price' => 18000,
            'stock_quantity' => 1,
            'alert_quantity' => 1,
            'is_published' => true,
        ]);
        $green = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Galaxy A16',
            'barcode' => 'A16-GREEN',
            'variant_group' => 'galaxy-a16',
            'color' => 'Green',
            'cost_price' => 15000,
            'selling_price' => 18000,
            'stock_quantity' => 1,
            'alert_quantity' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user, 'admin');

        $response = $this->put(route('products.update', $black), [
            'product_mode' => 'gadget',
            'name' => 'Galaxy A16 5G',
            'variant_group' => 'galaxy-a16',
            'cost_price' => 15500,
            'selling_price' => 19000,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_published' => 1,
            'removed_variant_ids' => [$green->id],
            'variants' => [
                [
                    'id' => $black->id,
                    'barcode' => 'A16-BLACK',
                    'color' => 'Graphite',
                    'cost_price' => 15500,
                    'selling_price' => 19000,
                ],
                [
                    'id' => $blue->id,
                    'barcode' => 'A16-BLUE',
                    'color' => 'Sky Blue',
                    'cost_price' => 15600,
                    'selling_price' => 19100,
                ],
            ],
        ]);

        $response->assertRedirect(route('products.index'));

        $black->refresh();
        $blue->refresh();
        $this->assertSame('Galaxy A16 5G', $black->name);
        $this->assertSame('Galaxy A16 5G', $blue->name);
        $this->assertSame('Graphite', $black->color);
        $this->assertSame('Sky Blue', $blue->color);
        $this->assertEquals(19100, (float) $blue->selling_price);

        $this->assertSoftDeleted('products', ['id' => $green->id]);
    }

    public function test_each_variant_keeps_its_own_description(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Desc Shop',
            'email' => 'desc-shop@example.com',
            'phone' => '01700000003',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $user->syncRoles(['Admin']);

        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Phones', 'slug' => 'phones-desc']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Apple', 'is_active' => true, 'sort_order' => 0]);

        $this->actingAs($user, 'admin')
            ->post(route('products.store'), [
                'product_mode' => 'gadget',
                'name' => 'iPhone 16',
                'variant_group' => 'iphone-16',
                'cost_price' => 90000,
                'selling_price' => 120000,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'is_published' => 1,
                'variants' => [
                    [
                        'barcode' => 'IP16-BLK-128',
                        'color' => 'Black',
                        'color_hex' => '#111111',
                        'storage' => '128GB',
                        'stock_quantity' => 1,
                        'description_rows' => [
                            ['heading' => 'Storage', 'detail' => '128 GB'],
                            ['heading' => 'Finish', 'detail' => 'Black titanium'],
                        ],
                    ],
                    [
                        'barcode' => 'IP16-BLU-256',
                        'color' => 'Blue',
                        'color_hex' => '#2563eb',
                        'storage' => '256GB',
                        'stock_quantity' => 1,
                        'description_rows' => [
                            ['heading' => 'Storage', 'detail' => '256 GB'],
                            ['heading' => 'Finish', 'detail' => 'Ultramarine'],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $black = Product::query()->where('barcode', 'IP16-BLK-128')->firstOrFail();
        $blue = Product::query()->where('barcode', 'IP16-BLU-256')->firstOrFail();

        $this->assertStringContainsString('128 GB', (string) $black->short_description);
        $this->assertStringContainsString('Black titanium', (string) $black->short_description);
        $this->assertStringContainsString('256 GB', (string) $blue->short_description);
        $this->assertStringContainsString('Ultramarine', (string) $blue->short_description);
        $this->assertNotSame($black->short_description, $blue->short_description);

        $this->actingAs($user, 'admin')
            ->put(route('products.update', $black), [
                'product_mode' => 'gadget',
                'name' => 'iPhone 16',
                'variant_group' => 'iphone-16',
                'cost_price' => 90000,
                'selling_price' => 120000,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'is_published' => 1,
                'variants' => [
                    [
                        'id' => $black->id,
                        'barcode' => 'IP16-BLK-128',
                        'color' => 'Black',
                        'description_rows' => [
                            ['heading' => 'Storage', 'detail' => '128 GB updated'],
                        ],
                    ],
                    [
                        'id' => $blue->id,
                        'barcode' => 'IP16-BLU-256',
                        'color' => 'Blue',
                        'description_rows' => [
                            ['heading' => 'Storage', 'detail' => '256 GB kept'],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $black->refresh();
        $blue->refresh();
        $this->assertStringContainsString('128 GB updated', (string) $black->short_description);
        $this->assertStringContainsString('256 GB kept', (string) $blue->short_description);
        $this->assertStringNotContainsString('128 GB updated', (string) $blue->short_description);
    }

    public function test_each_variant_keeps_its_own_stock_on_edit(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Stock Shop',
            'email' => 'stock-shop@example.com',
            'phone' => '01700000004',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $user->syncRoles(['Admin']);

        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Cables', 'slug' => 'cables-stock']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Baseus', 'is_active' => true, 'sort_order' => 0]);

        $black = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'USB-C Cable',
            'barcode' => 'CABLE-BLK',
            'variant_group' => 'usb-c-cable',
            'color' => 'Black',
            'cost_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 0,
            'alert_quantity' => 1,
            'is_published' => true,
            'requires_imei' => false,
        ]);
        $white = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'USB-C Cable',
            'barcode' => 'CABLE-WHT',
            'variant_group' => 'usb-c-cable',
            'color' => 'White',
            'cost_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 0,
            'alert_quantity' => 1,
            'is_published' => true,
            'requires_imei' => false,
        ]);

        $this->actingAs($user, 'admin')
            ->put(route('products.update', $black), [
                'product_mode' => 'gadget',
                'name' => 'USB-C Cable',
                'variant_group' => 'usb-c-cable',
                'cost_price' => 100,
                'selling_price' => 200,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'is_published' => 1,
                'variants' => [
                    [
                        'id' => $black->id,
                        'barcode' => 'CABLE-BLK',
                        'color' => 'Black',
                        'stock_quantity' => 12,
                    ],
                    [
                        'id' => $white->id,
                        'barcode' => 'CABLE-WHT',
                        'color' => 'White',
                        'stock_quantity' => 3,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $black->refresh();
        $white->refresh();
        $this->assertSame(12, (int) $black->stock_quantity);
        $this->assertSame(3, (int) $white->stock_quantity);
    }
}
