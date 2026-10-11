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

    public function test_editing_a_simple_product_can_add_sibling_variants(): void
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

        $this->actingAs($user);

        $response = $this->put(route('products.update', $product), [
            'name' => 'Redmi Note 17',
            'barcode' => 'RN17-BLACK-128',
            'variant_group' => 'redmi-note-17',
            'color' => 'Black',
            'color_hex' => '#111111',
            'storage' => '128GB',
            'ram' => '8GB',
            'cost_price' => 20000,
            'selling_price' => 25000,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_published' => 1,
            'expand_variants' => 1,
            'new_variants' => [
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
}
