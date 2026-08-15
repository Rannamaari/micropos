<?php

namespace App\Filament\Resources\StockCounts\Pages;

use App\Filament\Resources\StockCounts\StockCountResource;
use App\Filament\Support\AdminSupport;
use App\Models\Product;
use App\Models\StockCount;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateStockCount extends CreateRecord
{
    protected static string $resource = StockCountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $companyId = AdminSupport::companyId();
        $warehouseId = $data['warehouse_id'] ?? null;

        if (! $companyId || ! $warehouseId || ! AdminSupport::canAccessWarehouse($warehouseId)) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Select an authorized warehouse.',
            ]);
        }

        $productIds = Product::query()
            ->where('company_id', $companyId)
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('id')
            ->all();

        if ($productIds === []) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'There are no active inventory-tracked products available to count.',
            ]);
        }

        return app(InventoryService::class)->createStockCount(
            $companyId,
            $warehouseId,
            $productIds,
            auth()->id(),
            $data['notes'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        /** @var StockCount $record */
        $record = $this->getRecord();

        return StockCountResource::getUrl('count', ['record' => $record]);
    }
}
