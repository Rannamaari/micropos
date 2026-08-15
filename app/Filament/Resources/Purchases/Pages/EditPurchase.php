<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Support\AdminSupport;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Purchase) {
            return $record;
        }

        $companyId = AdminSupport::companyId();
        $warehouseId = $data['warehouse_id'] ?? null;
        $supplierId = $data['supplier_id'] ?? null;

        if (! $companyId || ! $warehouseId || ! AdminSupport::canAccessWarehouse($warehouseId)) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Select an authorized warehouse.',
            ]);
        }

        if (! $supplierId) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Select a supplier.',
            ]);
        }

        return app(PurchaseService::class)->updatePurchase(
            $record->id,
            $companyId,
            $warehouseId,
            $supplierId,
            $data['items'] ?? [],
            [
                'branch_id' => AdminSupport::authorizedWarehouseQuery()->whereKey($warehouseId)->value('branch_id'),
                'status' => $data['status'] ?? $record->status->value,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'shipping_total' => $data['shipping_total'] ?? 0,
                'other_cost_total' => $data['other_cost_total'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
