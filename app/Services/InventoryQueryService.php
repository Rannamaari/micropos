<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Enums\StockMovementType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryQueryService
{
    public function getBalance(string $companyId, string $warehouseId, string $productId): string
    {
        $balance = InventoryBalance::query()
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('quantity');

        return $this->formatDecimal($balance ?? 0);
    }

    /**
     * @param  array<int, string>  $productIds
     * @return Collection<string, string>
     */
    public function getBalancesForProducts(string $companyId, string $warehouseId, array $productIds): Collection
    {
        $balances = InventoryBalance::query()
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->pluck('quantity', 'product_id');

        return collect($productIds)->mapWithKeys(function (string $productId) use ($balances): array {
            return [$productId => $this->formatDecimal($balances[$productId] ?? 0)];
        });
    }

    public function warehouseInventory(string $companyId, string $warehouseId, int $perPage = 50): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage('page');

        $query = $this->warehouseInventoryQuery($companyId, $warehouseId)->orderBy('products.name');
        $total = (clone $query)->count('products.id');
        $items = $query->forPage($page, $perPage)->get();

        return new PaginationLengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function lowStockByWarehouse(string $companyId, string $warehouseId): Builder
    {
        return Product::query()
            ->select([
                'products.*',
                DB::raw('COALESCE(inventory_balances.quantity, 0) as current_quantity'),
            ])
            ->leftJoin('inventory_balances', function ($join) use ($companyId, $warehouseId): void {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.company_id', '=', $companyId)
                    ->where('inventory_balances.warehouse_id', '=', $warehouseId);
            })
            ->where('products.company_id', $companyId)
            ->where('products.track_inventory', true)
            ->where('products.is_active', true)
            ->whereRaw('COALESCE(inventory_balances.quantity, 0) <= products.minimum_stock')
            ->orderBy('products.name');
    }

    public function warehouseInventoryQuery(string $companyId, string $warehouseId): Builder
    {
        return Product::query()
            ->select([
                'products.id',
                'products.company_id',
                'products.category_id',
                'products.brand_id',
                'products.unit_id',
                'products.sku',
                'products.name',
                'products.minimum_stock',
                'products.cost_price',
                'products.selling_price',
                'products.is_active',
                'products.track_inventory',
                'products.allow_negative_stock',
                DB::raw('COALESCE(inventory_balances.quantity, 0) as current_quantity'),
                DB::raw('COALESCE(inventory_balances.quantity, 0) * products.cost_price as inventory_value'),
                'product_barcodes.barcode as primary_barcode',
                'categories.name as category_name',
                'brands.name as brand_name',
                'units.name as unit_name',
                'units.short_name as unit_short_name',
            ])
            ->leftJoin('inventory_balances', function ($join) use ($companyId, $warehouseId): void {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.company_id', '=', $companyId)
                    ->where('inventory_balances.warehouse_id', '=', $warehouseId);
            })
            ->leftJoin('product_barcodes', function ($join) use ($companyId): void {
                $join->on('product_barcodes.product_id', '=', 'products.id')
                    ->where('product_barcodes.company_id', '=', $companyId)
                    ->where('product_barcodes.is_primary', '=', true);
            })
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->where('products.company_id', $companyId)
            ->where('products.track_inventory', true)
            ->withExists([
                'stockMovements as has_opening_stock' => fn (Builder $query): Builder => $query
                    ->where('company_id', $companyId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('type', StockMovementType::Opening),
            ]);
    }

    public function inventoryValuation(string $companyId, string $warehouseId): string
    {
        $value = Product::query()
            ->leftJoin('inventory_balances', function ($join) use ($companyId, $warehouseId): void {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.company_id', '=', $companyId)
                    ->where('inventory_balances.warehouse_id', '=', $warehouseId);
            })
            ->where('products.company_id', $companyId)
            ->where('products.track_inventory', true)
            ->selectRaw('COALESCE(SUM(COALESCE(inventory_balances.quantity, 0) * products.cost_price), 0) as valuation')
            ->value('valuation');

        return number_format((float) $value, 4, '.', '');
    }

    public function movementHistory(string $companyId, array $filters = []): Builder
    {
        $query = StockMovement::query()
            ->with(['product', 'warehouse', 'creator'])
            ->where('company_id', $companyId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if ($warehouseId = $filters['warehouse_id'] ?? null) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($productId = $filters['product_id'] ?? null) {
            $query->where('product_id', $productId);
        }

        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }

        if ($from = $filters['from'] ?? null) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to = $filters['to'] ?? null) {
            $query->where('occurred_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function productInventorySnapshot(string $companyId, string $productId): Collection
    {
        return Warehouse::query()
            ->select([
                'warehouses.id',
                'warehouses.name',
                'warehouses.code',
                DB::raw('COALESCE(inventory_balances.quantity, 0) as current_quantity'),
            ])
            ->leftJoin('inventory_balances', function ($join) use ($companyId, $productId): void {
                $join->on('inventory_balances.warehouse_id', '=', 'warehouses.id')
                    ->where('inventory_balances.company_id', '=', $companyId)
                    ->where('inventory_balances.product_id', '=', $productId);
            })
            ->where('warehouses.company_id', $companyId)
            ->where('warehouses.is_active', true)
            ->orderByDesc('warehouses.is_default')
            ->orderBy('warehouses.name')
            ->get()
            ->map(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'current_quantity' => $this->formatDecimal($warehouse->current_quantity ?? 0),
            ]);
    }

    private function formatDecimal(float|string|int|null $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
