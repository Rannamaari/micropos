<?php

namespace App\Filament\Resources\InventoryOverview\Pages;

use App\Filament\Resources\InventoryOverview\InventoryOverviewResource;
use App\Filament\Resources\StockCounts\StockCountResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Support\AdminSupport;
use App\Services\InventoryQueryService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInventoryOverview extends ListRecords
{
    protected static string $resource = InventoryOverviewResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->tableFilters ??= [];
        $this->tableFilters['warehouse_id'] ??= ['value' => AdminSupport::resolveAuthorizedWarehouseId()];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canViewAny(), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stockCounts')
                ->label('Stock Counts')
                ->url(StockCountResource::getUrl('index'))
                ->visible(fn (): bool => auth()->user()?->can('inventory.count') ?? false),
            Action::make('stockMovements')
                ->label('Stock Movements')
                ->url(StockMovementResource::getUrl('index'))
                ->visible(fn (): bool => auth()->user()?->can('inventory.history') ?? false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $companyId = AdminSupport::companyId();
        $warehouseId = $this->getSelectedWarehouseId();

        abort_if(blank($companyId) || blank($warehouseId), 403);
        abort_unless(AdminSupport::canAccessWarehouse($warehouseId), 403);

        return app(InventoryQueryService::class)->warehouseInventoryQuery($companyId, $warehouseId);
    }

    public function getSelectedWarehouseId(): ?string
    {
        $state = $this->tableFilters['warehouse_id'] ?? null;

        if (is_array($state)) {
            return $state['value'] ?? null;
        }

        return is_string($state) ? $state : null;
    }
}
