<?php

namespace App\Filament\Widgets;

use App\Filament\Support\AdminSupport;
use App\Models\InventoryBalance;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\InventoryQueryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OperationsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Today at a Glance';

    protected function getStats(): array
    {
        $companyId = AdminSupport::companyId();

        if (! $companyId) {
            return [];
        }

        $todaySalesQuery = Sale::query()
            ->where('company_id', $companyId)
            ->whereDate('sale_date', today());

        $todaySales = (float) $todaySalesQuery->sum('grand_total');
        $todayTransactions = (int) (clone $todaySalesQuery)->count();

        $grossProfit = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.company_id', $companyId)
            ->whereDate('sales.sale_date', today())
            ->selectRaw('COALESCE(SUM((sale_items.unit_price - sale_items.unit_cost) * sale_items.quantity), 0) as gross_profit')
            ->value('gross_profit');

        $warehouseId = AdminSupport::activeWarehouseId();
        $inventoryQueryService = app(InventoryQueryService::class);

        $inventoryValue = $warehouseId
            ? (float) $inventoryQueryService->inventoryValuation($companyId, $warehouseId)
            : 0.0;

        $lowStockItems = $warehouseId
            ? $inventoryQueryService->lowStockByWarehouse($companyId, $warehouseId)->count()
            : 0;

        $outOfStockItems = $warehouseId
            ? (int) DB::table('products')
                ->leftJoin('inventory_balances', function ($join) use ($companyId, $warehouseId): void {
                    $join->on('inventory_balances.product_id', '=', 'products.id')
                        ->where('inventory_balances.company_id', '=', $companyId)
                        ->where('inventory_balances.warehouse_id', '=', $warehouseId);
                })
                ->where('products.company_id', $companyId)
                ->where('products.track_inventory', true)
                ->whereRaw('COALESCE(inventory_balances.quantity, 0) <= 0')
                ->count()
            : 0;

        $customerReceivables = (float) Sale::query()
            ->where('company_id', $companyId)
            ->sum('balance_due');

        $supplierPayables = (float) Purchase::query()
            ->where('company_id', $companyId)
            ->sum('balance_due');

        return [
            Stat::make("Today's Sales", number_format($todaySales, 2))
                ->description("{$todayTransactions} transactions")
                ->color('success'),
            Stat::make('Gross Profit Today', number_format($grossProfit, 2))
                ->color('success'),
            Stat::make('Inventory Value', number_format($inventoryValue, 2))
                ->description($warehouseId ? 'Active warehouse snapshot' : 'No warehouse context')
                ->color('info'),
            Stat::make('Low Stock Items', (string) $lowStockItems)
                ->color($lowStockItems > 0 ? 'warning' : 'success'),
            Stat::make('Out of Stock Items', (string) $outOfStockItems)
                ->color($outOfStockItems > 0 ? 'danger' : 'success'),
            Stat::make('Customer Receivables', number_format($customerReceivables, 2))
                ->color('warning'),
            Stat::make('Supplier Payables', number_format($supplierPayables, 2))
                ->color('warning'),
        ];
    }
}
