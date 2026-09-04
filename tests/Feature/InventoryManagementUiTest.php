<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Filament\Resources\InventoryOverview\Pages\ListInventoryOverview;
use App\Filament\Resources\StockCounts\Pages\CountStock;
use App\Filament\Resources\StockCounts\Pages\CreateStockCount;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\Company;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryManagementUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function inventory_overview_shows_zero_stock_and_statuses(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $unit = Unit::factory()->create();

        $inStock = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => $unit->id,
            'name' => 'Coca-Cola 500ml',
            'sku' => 'COKE500',
            'minimum_stock' => 10,
        ]);
        $lowStock = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => $unit->id,
            'name' => 'Pepsi 500ml',
            'sku' => 'PEPSI500',
            'minimum_stock' => 10,
        ]);
        $outOfStock = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => $unit->id,
            'name' => 'Water 1.5L',
            'sku' => 'WATER1500',
            'minimum_stock' => 4,
        ]);
        $negative = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => $unit->id,
            'name' => 'Milo Tin',
            'sku' => 'MILO001',
            'minimum_stock' => 2,
            'allow_negative_stock' => true,
        ]);

        InventoryBalance::factory()->create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $inStock->id,
            'quantity' => '46.0000',
        ]);
        InventoryBalance::factory()->create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $lowStock->id,
            'quantity' => '5.0000',
        ]);
        InventoryBalance::factory()->create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $negative->id,
            'quantity' => '-1.0000',
        ]);

        $this->actingAs($user)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Coca-Cola 500ml')
            ->assertSee('Pepsi 500ml')
            ->assertSee('Water 1.5L')
            ->assertSee('Milo Tin')
            ->assertSee('IN STOCK')
            ->assertSee('LOW STOCK')
            ->assertSee('OUT OF STOCK')
            ->assertSee('NEGATIVE STOCK');
    }

    #[Test]
    public function inventory_overview_is_company_scoped_and_warehouse_aware(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create(['company_id' => $warehouse->company_id, 'branch_id' => $warehouse->branch_id]);
        $user = $this->userWithRole('admin', $warehouse);
        $unit = Unit::factory()->create();

        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => $unit->id,
            'name' => 'Warehouse Soda',
            'sku' => 'WH-SODA',
        ]);

        InventoryBalance::factory()->create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '25.0000',
        ]);
        InventoryBalance::factory()->create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $otherWarehouse->id,
            'product_id' => $product->id,
            'quantity' => '8.0000',
        ]);

        $otherCompany = Company::factory()->create();
        Product::factory()->create([
            'company_id' => $otherCompany->id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Other Company Inventory',
            'sku' => 'OTHER-INV',
        ]);

        $this->actingAs($user)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Warehouse Soda')
            ->assertDontSee('Other Company Inventory')
            ->assertSee('25.0000');

        $secondWarehouseUser = $this->userWithRole('admin', $otherWarehouse);

        $this->actingAs($secondWarehouseUser)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('8.0000')
            ->assertDontSee('25.0000');

        $this->actingAs($user)
            ->get('/admin/inventory?filters[warehouse_id][value]='.$otherWarehouse->id)
            ->assertForbidden();
    }

    #[Test]
    public function inventory_overview_searches_sku_without_matching_product_names_or_barcodes(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Scanner Cola',
            'sku' => 'SCN-002-COLA',
        ]);

        ProductBarcode::factory()->create([
            'company_id' => $warehouse->company_id,
            'product_id' => $product->id,
            'barcode' => '1234567890',
            'is_primary' => true,
        ]);

        $nameOnlyProduct = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => '002 only in product name',
            'sku' => 'NAME-ONLY',
        ]);

        $barcodeOnlyProduct = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => '002 only in barcode',
            'sku' => 'BARCODE-ONLY',
        ]);

        ProductBarcode::factory()->create([
            'company_id' => $warehouse->company_id,
            'product_id' => $barcodeOnlyProduct->id,
            'barcode' => 'BARCODE-002',
            'is_primary' => true,
        ]);

        $component = Livewire::actingAs($user)->test(ListInventoryOverview::class);

        $component->set('tableSearch', 'SCN-002-COLA')->assertSee('Scanner Cola');
        $component->set('tableSearch', '002')
            ->assertSee('Scanner Cola')
            ->assertDontSee($nameOnlyProduct->name)
            ->assertDontSee($barcodeOnlyProduct->name);
        $component->set('tableSearch', '1234567890')->assertSee('Scanner Cola');
    }

    #[Test]
    public function inventory_actions_use_inventory_service_and_create_movements(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Action Cola',
            'sku' => 'ACT-COLA',
            'minimum_stock' => 10,
        ]);

        $component = Livewire::actingAs($user)->test(ListInventoryOverview::class);

        $component->callTableAction('setOpeningStock', $product->getKey(), data: [
            'opening_quantity' => 50,
            'unit_cost' => 10,
            'reason' => 'Opening inventory',
            'notes' => 'Initial stock',
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '50.0000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => StockMovementType::Opening->value,
            'quantity' => '50.0000',
            'quantity_after' => '50.0000',
        ]);

        $component->callTableAction('adjustStock', $product->getKey(), data: [
            'actual_quantity' => 45,
            'reason' => 'Physical count correction',
            'notes' => 'Counted on shelf',
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'quantity' => '45.0000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::AdjustmentOut->value,
            'quantity' => '-5.0000',
            'quantity_after' => '45.0000',
        ]);

        $component->callTableAction('recordDamage', $product->getKey(), data: [
            'quantity' => 3,
            'reason' => 'Damaged in storage',
            'notes' => 'Broken bottle',
        ]);

        $component->callTableAction('recordLoss', $product->getKey(), data: [
            'quantity' => 2,
            'reason' => 'Missing during physical count',
            'notes' => 'Not found in aisle',
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'quantity' => '40.0000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Damage->value,
            'quantity' => '-3.0000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Loss->value,
            'quantity' => '-2.0000',
        ]);
    }

    #[Test]
    public function stock_movements_history_is_visible_and_read_only(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'History Cola',
            'sku' => 'HIS-COLA',
        ]);

        app(InventoryService::class)->setOpeningStock($warehouse->company_id, $warehouse->id, $product->id, 20, 10, $user->id, null, 'Opening inventory');
        app(InventoryService::class)->adjust($warehouse->company_id, $warehouse->id, $product->id, 18, $user->id, 'Physical count correction', 'Adjusted');

        $this->actingAs($user)
            ->get('/admin/stock-movements')
            ->assertOk()
            ->assertSee('History Cola')
            ->assertSee('opening')
            ->assertSee('adjustment_out')
            ->assertSee('Physical count correction')
            ->assertDontSee('Delete');
    }

    #[Test]
    public function product_edit_page_shows_inventory_snapshot(): void
    {
        $warehouse = Warehouse::factory()->create();
        $secondWarehouse = Warehouse::factory()->create(['company_id' => $warehouse->company_id, 'branch_id' => $warehouse->branch_id, 'name' => 'Store Room']);
        $user = $this->userWithRole('admin', $warehouse);
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Snapshot Cola',
            'sku' => 'SNAP-COLA',
            'minimum_stock' => 10,
        ]);

        InventoryBalance::factory()->create([
            'company_id' => $warehouse->company_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '46.0000',
        ]);

        $this->actingAs($user)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Inventory')
            ->assertSee($warehouse->name)
            ->assertSee($secondWarehouse->name)
            ->assertSee('46.0000')
            ->assertSee('0.0000')
            ->assertSee('Total Across Warehouses');
    }

    #[Test]
    public function stock_count_creation_page_creates_a_counting_worksheet(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);

        $trackedProduct = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'track_inventory' => false,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CreateStockCount::class)
            ->fillForm([
                'warehouse_id' => $warehouse->id,
                'notes' => 'Weekly shelf count',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $count = StockCount::query()->first();

        $this->assertNotNull($count);
        $this->assertSame($warehouse->id, $count->warehouse_id);
        $this->assertDatabaseHas('stock_count_items', [
            'stock_count_id' => $count->id,
            'product_id' => $trackedProduct->id,
        ]);
    }

    #[Test]
    public function stock_count_worksheet_accepts_counts_and_completion_updates_inventory(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->userWithRole('admin', $warehouse);
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'name' => 'Worksheet Cola',
            'sku' => 'WS-COLA',
            'minimum_stock' => 10,
        ]);

        app(InventoryService::class)->setOpeningStock($warehouse->company_id, $warehouse->id, $product->id, 50, 10, $user->id, null, 'Opening inventory');

        $count = app(InventoryService::class)->createStockCount(
            $warehouse->company_id,
            $warehouse->id,
            [$product->id],
            $user->id,
            'Cycle count',
        );

        /** @var StockCountItem $item */
        $item = $count->items()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CountStock::class, ['record' => $count->id])
            ->callTableAction('enterCount', $item->id, data: [
                'counted_quantity' => 45,
            ])
            ->assertSee('45.0000')
            ->callAction('completeCount');

        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '45.0000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => StockMovementType::StockCount->value,
            'quantity' => '-5.0000',
            'quantity_after' => '45.0000',
        ]);
    }

    private function userWithRole(string $role, Warehouse $warehouse, bool $assignWarehouse = true): User
    {
        $user = $assignWarehouse
            ? User::factory()->forWarehouse($warehouse)->create()
            : User::factory()->forBranch($warehouse->branch)->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
