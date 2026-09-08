<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $nested) use ($search): void {
                            $nested->whereLike('products.name', "%{$search}%", caseSensitive: false)
                                ->orWhereLike('products.sku', "%{$search}%", caseSensitive: false)
                                ->orWhereHas('barcodes', fn (Builder $barcodeQuery): Builder => $barcodeQuery->whereLike('barcode', "%{$search}%", caseSensitive: false));
                        });
                    })
                    ->sortable(),
                TextColumn::make('primaryBarcode.barcode')
                    ->label('Primary Barcode')
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->toggleable(),
                TextColumn::make('brand.name')
                    ->toggleable(),
                TextColumn::make('unit.short_name')
                    ->label('Unit'),
                TextColumn::make('cost_price')
                    ->money('MVR')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->money('MVR')
                    ->sortable(),
                TextColumn::make('minimum_stock')
                    ->sortable(),
                IconColumn::make('track_inventory')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name'),
                SelectFilter::make('brand_id')
                    ->relationship('brand', 'name'),
                SelectFilter::make('track_inventory')
                    ->options([
                        1 => 'Tracked',
                        0 => 'Not Tracked',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('deleteProduct')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->can('products.deactivate') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Product $record): string => "Delete {$record->name}")
                    ->modalDescription(fn (Product $record): string => ProductResource::deletionBlockMessage($record) ?? 'This permanently removes the product and its store-specific prices.')
                    ->modalSubmitActionLabel(fn (Product $record): string => ProductResource::deletionBlockMessage($record) ? 'Keep Product' : 'Delete Product')
                    ->action(function (Product $record): void {
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
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->fetchSelectedRecords()
                        ->authorizeIndividualRecords(fn (Product $record): bool => ProductResource::canDelete($record)),
                ]),
            ])
            ->defaultSort('name')
            ->paginated([25, 50, 100]);
    }
}
