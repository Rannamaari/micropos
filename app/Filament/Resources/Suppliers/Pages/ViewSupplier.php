<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_purchase_order')
                ->label('New Purchase Order')
                ->icon('heroicon-o-shopping-cart')
                ->visible(fn (): bool => auth()->user()?->can('purchases.create'))
                ->url(fn (): string => PurchaseResource::getUrl('create', ['supplier_id' => $this->getRecord()->id])),
            EditAction::make(),
        ];
    }
}
