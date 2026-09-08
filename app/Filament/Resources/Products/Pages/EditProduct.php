<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\InventoryOverview\InventoryOverviewResource;
use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductPurgeService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
            Action::make('deleteProduct')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->can('products.deactivate') ?? false)
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'Delete '.$this->getRecordTitle())
                ->modalDescription(fn (): string => ProductResource::deletionBlockMessage($this->getRecord()) ?? 'This permanently removes the product and its store-specific prices.')
                ->modalSubmitActionLabel(fn (): string => ProductResource::deletionBlockMessage($this->getRecord()) ? 'Keep Product' : 'Delete Product')
                ->action(function (): void {
                    $record = $this->getRecord();

                    if ($message = ProductResource::deletionBlockMessage($record)) {
                        Notification::make()
                            ->title('Product cannot be deleted')
                            ->body($message)
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $record->delete();

                    Notification::make()->title('Product deleted.')->success()->send();
                    $this->redirect(ProductResource::getUrl('index'));
                }),
            Action::make('purgeTestProduct')
                ->label('Purge Test Product')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->hasRole('super-admin') ?? false)
                ->authorize(fn (): bool => auth()->user()?->hasRole('super-admin') ?? false)
                ->requiresConfirmation()
                ->modalHeading('Purge Test Product')
                ->modalDescription('For test data only. This permanently removes the product and its inventory-only history. Products with sales or purchase history cannot be purged.')
                ->schema([
                    TextInput::make('sku_confirmation')
                        ->label(fn (): string => "Type {$this->getRecord()->sku} to confirm")
                        ->required()
                        ->helperText('This action cannot be undone.'),
                ])
                ->modalSubmitActionLabel('Purge Permanently')
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    if ($data['sku_confirmation'] !== $record->sku) {
                        Notification::make()
                            ->title('SKU confirmation did not match')
                            ->body('The product was not changed.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        app(ProductPurgeService::class)->purgeInventoryOnlyProduct($record);
                    } catch (\LogicException $exception) {
                        Notification::make()
                            ->title('Product cannot be purged')
                            ->body($exception->getMessage().' Keep the product inactive instead.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()->title('Test product and inventory history purged.')->success()->send();
                    $this->redirect(ProductResource::getUrl('index'));
                }),
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
