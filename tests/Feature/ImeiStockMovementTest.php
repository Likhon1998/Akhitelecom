<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImei;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Shop;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImeiStockMovementTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    private Product $phone;

    private StockLocation $store;

    private StockLocation $warehouse;

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

        app(StockService::class)->ensureDefaultLocations($this->shop->id);
        $this->store = StockLocation::where('shop_id', $this->shop->id)->where('type', 'store')->firstOrFail();
        $this->warehouse = StockLocation::where('shop_id', $this->shop->id)->where('type', 'warehouse')->first()
            ?? StockLocation::create(['shop_id' => $this->shop->id, 'name' => 'Main Warehouse', 'type' => 'warehouse', 'is_active' => true]);

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

        $this->actingAs($this->admin);
    }

    private function imei(string $imei): ProductImei
    {
        return ProductImei::where('imei', $imei)->firstOrFail();
    }

    private function stock(): int
    {
        return (int) $this->phone->fresh()->stock_quantity;
    }

    public function test_stock_out_adjustment_removes_the_chosen_phones(): void
    {
        $this->post(route('supply.adjustments.store'), [
            'product_id' => $this->phone->id,
            'type' => 'out',
            'quantity' => 1,
            'reference' => 'Lost in count',
            'imeis' => ['359123000000091'],
        ])->assertSessionHas('success');

        $this->assertSame(ProductImei::STATUS_REMOVED, $this->imei('359123000000001')->status);
        $this->assertSame(2, $this->stock());
    }

    public function test_stock_out_adjustment_needs_one_imei_per_phone(): void
    {
        $this->post(route('supply.adjustments.store'), [
            'product_id' => $this->phone->id,
            'type' => 'out',
            'quantity' => 2,
            'reference' => 'Count',
            'imeis' => ['359123000000002'],
        ])->assertSessionHas('error', 'Choose the IMEI of each phone for iPhone 17 Pro Max (2 needed, 1 chosen).');

        $this->assertSame(3, $this->stock());
        $this->assertSame(ProductImei::STATUS_AVAILABLE, $this->imei('359123000000002')->status);
    }

    public function test_stock_in_adjustment_registers_new_phones_and_rejects_sold_ones(): void
    {
        $this->post(route('supply.adjustments.store'), [
            'product_id' => $this->phone->id,
            'type' => 'in',
            'quantity' => 2,
            'reference' => 'Found in back room',
            'phones' => [
                ['imei' => '359123000000004', 'imei2' => '359123000000094'],
                ['imei' => '359123000000005', 'imei2' => null],
            ],
        ])->assertSessionHas('success');

        $this->assertSame(5, $this->stock());
        $this->assertSame('359123000000094', $this->imei('359123000000004')->imei_2);
        $this->assertSame(ProductImei::STATUS_AVAILABLE, $this->imei('359123000000005')->status);

        $this->imei('359123000000002')->update(['status' => ProductImei::STATUS_SOLD]);
        $this->post(route('supply.adjustments.store'), [
            'product_id' => $this->phone->id,
            'type' => 'in',
            'quantity' => 1,
            'reference' => 'Recount',
            'phones' => [['imei' => '359123000000002']],
        ])->assertSessionHas('error', 'IMEI 359123000000002 was sold — take customer returns through a refund or exchange.');
        $this->assertSame(5, $this->stock());
    }

    public function test_damage_marks_the_phone_damaged_and_it_can_come_back_after_repair(): void
    {
        $this->post(route('supply.damage-products.store'), [
            'product_id' => $this->phone->id,
            'quantity' => 1,
            'reference' => 'Cracked screen',
            'imeis' => ['359123000000003'],
        ])->assertSessionHas('success');

        $this->assertSame(ProductImei::STATUS_DAMAGED, $this->imei('359123000000003')->status);
        $this->assertSame(2, $this->stock());

        $this->post(route('supply.adjustments.store'), [
            'product_id' => $this->phone->id,
            'type' => 'in',
            'quantity' => 1,
            'reference' => 'Repaired',
            'phones' => [['imei' => '359123000000003']],
        ])->assertSessionHas('success');

        $this->assertSame(ProductImei::STATUS_AVAILABLE, $this->imei('359123000000003')->status);
        $this->assertSame(3, $this->stock());
        $this->assertSame(1, ProductImei::where('imei', '359123000000003')->count());
    }

    public function test_damage_rejects_a_phone_that_is_not_in_stock(): void
    {
        $this->post(route('supply.damage-products.store'), [
            'product_id' => $this->phone->id,
            'quantity' => 1,
            'reference' => 'Broken',
            'imeis' => ['359999999999999'],
        ])->assertSessionHas('error', 'IMEI 359999999999999 of iPhone 17 Pro Max is not in stock.');

        $this->assertSame(3, $this->stock());
    }

    private function purchaseOrder(int $qty): PurchaseOrderItem
    {
        $supplier = Supplier::create(['shop_id' => $this->shop->id, 'name' => 'Apple BD']);
        $po = PurchaseOrder::create([
            'shop_id' => $this->shop->id,
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'po_number' => 'PO-TEST-1',
            'status' => 'ordered',
            'order_date' => now()->toDateString(),
            'total_amount' => 180000 * $qty,
        ]);

        return PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->phone->id,
            'quantity' => $qty,
            'unit_cost' => 180000,
            'subtotal' => 180000 * $qty,
        ]);
    }

    public function test_purchase_receive_into_store_adds_each_phone_by_imei(): void
    {
        $item = $this->purchaseOrder(2);

        $this->post(route('supply.purchase-orders.receive', $item->purchase_order_id), [
            'receive_location_id' => $this->store->id,
            'items' => [['id' => $item->id, 'receive_qty' => 1, 'phones' => []]],
        ])->assertSessionHas('error', 'Enter the IMEI of each phone for iPhone 17 Pro Max (1 needed, 0 entered).');
        $this->assertSame(3, $this->stock());

        $this->post(route('supply.purchase-orders.receive', $item->purchase_order_id), [
            'receive_location_id' => $this->store->id,
            'items' => [['id' => $item->id, 'receive_qty' => 2, 'phones' => [
                ['imei' => '359123000000006', 'imei2' => ''],
                ['imei' => '359123000000007', 'imei2' => '359123000000097'],
            ]]],
        ])->assertSessionHas('success');

        $this->assertSame(5, $this->stock());
        $this->assertSame(ProductImei::STATUS_AVAILABLE, $this->imei('359123000000006')->status);
        $this->assertSame('359123000000097', $this->imei('359123000000007')->imei_2);
    }

    public function test_warehouse_phones_are_not_sellable_until_transferred(): void
    {
        $item = $this->purchaseOrder(2);

        $this->post(route('supply.purchase-orders.receive', $item->purchase_order_id), [
            'receive_location_id' => $this->warehouse->id,
            'items' => [['id' => $item->id, 'receive_qty' => 2, 'phones' => [
                ['imei' => '359123000000006'],
                ['imei' => '359123000000007'],
            ]]],
        ])->assertSessionHas('success');

        $this->assertSame(3, $this->stock());
        $held = $this->imei('359123000000006');
        $this->assertSame(ProductImei::STATUS_WAREHOUSE, $held->status);
        $this->assertSame($this->warehouse->id, (int) $held->location_id);
        $this->assertSame(3, $this->phone->availableImeis()->count());

        $this->post(route('supply.stock-transfers.store'), [
            'from_location_id' => $this->warehouse->id,
            'to_location_id' => $this->store->id,
            'items' => [['product_id' => $this->phone->id, 'quantity' => 1, 'imeis' => ['359123000000006']]],
        ])->assertSessionHas('success');

        $this->assertSame(4, $this->stock());
        $this->assertSame(ProductImei::STATUS_AVAILABLE, $this->imei('359123000000006')->status);
        $this->assertNull($this->imei('359123000000006')->location_id);
        $this->assertSame(ProductImei::STATUS_WAREHOUSE, $this->imei('359123000000007')->status);
        $this->assertSame(1, (int) WarehouseStock::where('location_id', $this->warehouse->id)->value('quantity'));
    }

    public function test_store_to_warehouse_transfer_moves_the_chosen_phone_out_of_sale(): void
    {
        $this->post(route('supply.stock-transfers.store'), [
            'from_location_id' => $this->store->id,
            'to_location_id' => $this->warehouse->id,
            'items' => [['product_id' => $this->phone->id, 'quantity' => 1, 'imeis' => ['359123000000002']]],
        ])->assertSessionHas('success');

        $this->assertSame(2, $this->stock());
        $this->assertSame(ProductImei::STATUS_WAREHOUSE, $this->imei('359123000000002')->status);
        $this->assertSame($this->warehouse->id, (int) $this->imei('359123000000002')->location_id);
    }

    public function test_purchase_return_sends_the_chosen_phone_back_to_supplier(): void
    {
        $supplier = Supplier::create(['shop_id' => $this->shop->id, 'name' => 'Apple BD']);

        $this->post(route('supply.purchase-returns.store'), [
            'supplier_id' => $supplier->id,
            'return_location_id' => $this->store->id,
            'items' => [['product_id' => $this->phone->id, 'quantity' => 1, 'unit_cost' => 180000, 'imeis' => ['359123000000002']]],
        ])->assertRedirect(route('supply.purchase-returns.index'));

        $this->assertSame(ProductImei::STATUS_SUPPLIER_RETURN, $this->imei('359123000000002')->status);
        $this->assertSame(2, $this->stock());
    }

    public function test_opening_inventory_refuses_phones_without_imeis(): void
    {
        $other = Product::create([
            'shop_id' => $this->shop->id,
            'name' => 'Pixel 10',
            'barcode' => 'PX10',
            'cost_price' => 90000,
            'selling_price' => 99000,
            'stock_quantity' => 0,
            'requires_imei' => true,
        ]);

        $this->get(route('supply.opening-inventory.index'))->assertOk()->assertSee('Phone — add each IMEI');

        $this->post(route('supply.opening-inventory.store'), [
            'items' => [['product_id' => $other->id, 'quantity' => 3]],
        ])->assertSessionHas('error');

        $this->assertSame(0, (int) $other->fresh()->stock_quantity);
    }

    public function test_supply_pages_list_the_phones_in_stock(): void
    {
        $this->get(route('supply.adjustments.index'))->assertOk()->assertSee('359123000000002');
        $this->get(route('supply.damage-products.index'))->assertOk()->assertSee('359123000000002');
        $this->get(route('supply.stock-transfers.create'))->assertOk()->assertSee('359123000000002');
        $this->get(route('supply.purchase-returns.create'))->assertOk()->assertSee('359123000000002');

        $item = $this->purchaseOrder(1);
        $this->get(route('supply.purchase-orders.show', $item->purchase_order_id))
            ->assertOk()
            ->assertSee('IMEI of each phone');
    }
}
