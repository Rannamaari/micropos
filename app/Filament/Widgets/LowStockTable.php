<?php

namespace App\Filament\Widgets;

use App\Filament\Support\AdminSupport;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockTable extends TableWidget
{
    protected static ?string $heading = 'Low Stock Alerts';

    protected string|int|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->description('Active warehouse items at or below their minimum stock level.')
            ->query($this->query())
            ->columns([
                TextColumn::make('name')->label('Product')->description(fn (Product $record): string => "SKU: {$record->sku}")->limit(28),
                TextColumn::make('current_quantity')->label('On Hand')->numeric(decimalPlaces: 2)->badge()->color(fn ($state): string => (float) $state <= 0 ? 'danger' : 'warning'),
                TextColumn::make('minimum_stock')->label('Minimum')->numeric(decimalPlaces: 2)->alignEnd(),
            ])
            ->defaultPaginationPageOption(5);
    }

    /** @return Builder<Product> */
    private function query(): Builder
    {
        $companyId = AdminSupport::companyId();
        $warehouseId = AdminSupport::activeWarehouseId();

        if (! $companyId || ! $warehouseId) {
            return Product::query()->whereRaw('1 = 0');
        }

        return Product::query()
            ->leftJoin('inventory_balances', function ($join) use ($companyId, $warehouseId): void {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->where('inventory_balances.company_id', '=', $companyId)
                    ->where('inventory_balances.warehouse_id', '=', $warehouseId);
            })
            ->where('products.company_id', $companyId)
            ->where('products.is_active', true)
            ->where('products.track_inventory', true)
            ->select('products.*')
            ->selectRaw('COALESCE(inventory_balances.quantity, 0) as current_quantity')
            ->whereRaw('COALESCE(inventory_balances.quantity, 0) <= products.minimum_stock')
            ->orderBy('current_quantity')
            ->orderBy('products.name');
    }
}
