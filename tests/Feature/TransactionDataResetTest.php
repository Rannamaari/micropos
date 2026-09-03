<?php

namespace Tests\Feature;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SalesService;
use App\Services\TransactionDataResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDataResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_reset_removes_sales_and_restores_remaining_stock_history(): void
    {
        [$warehouse, $product] = $this->warehouseAndTrackedProduct();
        app(InventoryService::class)->setOpeningStock($warehouse->company_id, $warehouse->id, $product->id, 10, 5);
        app(SalesService::class)->createSale($warehouse->company_id, $warehouse->branch_id, $warehouse->id, [['product_id' => $product->id, 'quantity' => 2]], [['amount' => 20]]);

        $result = app(TransactionDataResetService::class)->resetSales($warehouse->company_id);

        $this->assertSame(1, $result['sales']);
        $this->assertSame(0, Sale::query()->where('company_id', $warehouse->company_id)->count());
        $this->assertSame('10.0000', app(InventoryService::class)->getBalance($warehouse->company_id, $warehouse->id, $product->id));
        $this->assertSame(1, StockMovement::query()->where('company_id', $warehouse->company_id)->count());
    }

    public function test_full_transaction_reset_keeps_master_data_and_removes_inventory_data(): void
    {
        [$warehouse, $product] = $this->warehouseAndTrackedProduct();
        app(InventoryService::class)->setOpeningStock($warehouse->company_id, $warehouse->id, $product->id, 10, 5);

        app(TransactionDataResetService::class)->resetAllTransactions($warehouse->company_id);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertSame(0, StockMovement::query()->where('company_id', $warehouse->company_id)->count());
        $this->assertSame(0, InventoryBalance::query()->where('company_id', $warehouse->company_id)->count());
    }

    /** @return array{Warehouse, Product} */
    private function warehouseAndTrackedProduct(): array
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'company_id' => $warehouse->company_id,
            'unit_id' => Unit::factory()->create()->id,
            'selling_price' => 10,
            'cost_price' => 5,
            'tax_rate' => 0,
            'track_inventory' => true,
        ]);

        return [$warehouse, $product];
    }
}
