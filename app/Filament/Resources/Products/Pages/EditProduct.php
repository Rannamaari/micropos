<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\InventoryOverview\InventoryOverviewResource;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateProduct')
                ->label('Update Product')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(function (): void {
                    $this->save(false);
                }),
            Action::make('inventoryOverview')
                ->label('Inventory Overview')
                ->url(fn (): string => InventoryOverviewResource::getUrl('index', ['search' => $this->record->sku])),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Update Product'),
            $this->getCancelFormAction(),
        ];
    }
}
