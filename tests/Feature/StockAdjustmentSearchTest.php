<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_adjustment_page_has_product_search_for_restock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Test Shop',
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

        $cable = Product::create([
            'shop_id' => $shop->id,
            'name' => 'USB-C Cable Pro',
            'barcode' => 'USB-C-PRO-1',
            'cost_price' => 50,
            'selling_price' => 120,
            'stock_quantity' => 2,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('supply.adjustments.index'))
            ->assertOk()
            ->assertSee('Search by name, barcode or SKU', false)
            ->assertSee('stockAdjustmentForm', false)
            ->assertSee('USB-C Cable Pro', false)
            ->assertSee('USB-C-PRO-1', false);

        $this->actingAs($admin, 'admin')
            ->post(route('supply.adjustments.store'), [
                'product_id' => $cable->id,
                'type' => 'in',
                'quantity' => 5,
                'reference' => 'Restock search test',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(7, (int) $cable->fresh()->stock_quantity);
    }
}
