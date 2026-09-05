<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\AdminSupport;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array{warehouse_id: ?string, quantity: mixed, unit_cost: mixed} */
    protected array $openingStock = [];

    /**
     * Keep creation-only inventory fields out of the product model.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->openingStock = [
            'warehouse_id' => $data['opening_warehouse_id'] ?? null,
            'quantity' => $data['opening_quantity'] ?? 0,
            'unit_cost' => $data['opening_unit_cost'] ?? null,
        ];

        unset($data['opening_warehouse_id'], $data['opening_quantity'], $data['opening_unit_cost']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = parent::handleRecordCreation($data);

        if ((float) $this->openingStock['quantity'] <= 0) {
            return $product;
        }

        abort_unless(AdminSupport::user()?->can('inventory.opening'), 403);

        $warehouseId = $this->openingStock['warehouse_id'];
        $warehouseIsValid = $warehouseId && Warehouse::query()
            ->whereKey($warehouseId)
            ->where('company_id', $product->company_id)
            ->where('is_active', true)
            ->exists();

        if (! $warehouseIsValid) {
            throw ValidationException::withMessages([
                'opening_warehouse_id' => 'Select an active warehouse for the opening stock.',
            ]);
        }

        app(InventoryService::class)->setOpeningStock(
            $product->company_id,
            $warehouseId,
            $product->id,
            $this->openingStock['quantity'],
            filled($this->openingStock['unit_cost']) ? (float) $this->openingStock['unit_cost'] : (float) $product->cost_price,
            AdminSupport::user()?->id,
            null,
            'Opening stock entered while creating the product.',
        );

        return $product;
    }

    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Product added.')
            ->body('Add another product or return to the product list.')
            ->actions([
                Action::make('add_another_product')
                    ->label('Add Another Product')
                    ->url(ProductResource::getUrl('create')),
                Action::make('view_products')
                    ->label('View Products')
                    ->url(ProductResource::getUrl('index')),
            ]);
    }
}
