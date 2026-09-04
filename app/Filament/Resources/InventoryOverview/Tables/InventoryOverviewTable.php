<?php

namespace App\Filament\Resources\InventoryOverview\Tables;

use App\Enums\StockMovementType;
use App\Filament\Resources\InventoryOverview\Pages\ListInventoryOverview;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Support\AdminSupport;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\InventoryStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryOverviewTable
{
    public static function configure(Table $table): Table
    {
        $currency = AdminSupport::company()?->currency ?: 'MVR';

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->description(fn (Product $record): string => 'SKU: '.$record->sku.($record->primary_barcode ? ' | Barcode: '.$record->primary_barcode : ''))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('products.name', $direction)),
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primary_barcode')
                    ->label('Primary Barcode')
                    ->toggleable(),
                TextColumn::make('category_name')
                    ->label('Category')
                    ->toggleable(),
                TextColumn::make('brand_name')
                    ->label('Brand')
                    ->toggleable(),
                TextColumn::make('unit_short_name')
                    ->label('Unit')
                    ->toggleable(),
                TextColumn::make('current_quantity')
                    ->label('Current Stock')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 4, '.', ''))
                    ->sortable(),
                TextColumn::make('minimum_stock')
                    ->label('Minimum Stock')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 4, '.', ''))
                    ->sortable(),
                TextColumn::make('inventory_status')
                    ->label('Status')
                    ->state(fn (Product $record): string => InventoryStatus::forProduct(
                        (bool) $record->track_inventory,
                        (float) $record->current_quantity,
                        (float) $record->minimum_stock,
                    ))
                    ->badge()
                    ->color(fn (string $state): string => InventoryStatus::color($state)),
                TextColumn::make('cost_price')
                    ->money($currency)
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->money($currency)
                    ->sortable(),
                TextColumn::make('inventory_value')
                    ->money($currency)
                    ->sortable(),
            ])
            ->searchPlaceholder('Search SKU')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn (): array => AdminSupport::warehouseOptions())
                    ->default(AdminSupport::resolveAuthorizedWarehouseId())
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => AdminSupport::categoryOptions()),
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn (): array => AdminSupport::brandOptions()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'in_stock' => InventoryStatus::IN_STOCK,
                        'low_stock' => InventoryStatus::LOW_STOCK,
                        'out_of_stock' => InventoryStatus::OUT_OF_STOCK,
                        'negative_stock' => InventoryStatus::NEGATIVE_STOCK,
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'in_stock' => $query->whereRaw('COALESCE(inventory_balances.quantity, 0) > products.minimum_stock'),
                            'low_stock' => $query->whereRaw('COALESCE(inventory_balances.quantity, 0) > 0 AND COALESCE(inventory_balances.quantity, 0) <= products.minimum_stock'),
                            'out_of_stock' => $query->whereRaw('COALESCE(inventory_balances.quantity, 0) = 0'),
                            'negative_stock' => $query->whereRaw('COALESCE(inventory_balances.quantity, 0) < 0'),
                            default => $query,
                        };
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions([
                Action::make('viewProduct')
                    ->label('View Product')
                    ->url(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (): bool => auth()->user()?->can('products.view') ?? false),
                Action::make('setOpeningStock')
                    ->label('Set Opening Stock')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (Product $record): bool => (auth()->user()?->can('inventory.opening') ?? false) && ! (bool) $record->has_opening_stock)
                    ->schema([
                        Placeholder::make('warehouse')->content(fn (ListInventoryOverview $livewire): string => static::selectedWarehouseName($livewire)),
                        Placeholder::make('current_stock')->content(fn (Product $record): string => number_format((float) $record->current_quantity, 4, '.', '')),
                        TextInput::make('opening_quantity')->required()->numeric()->minValue(0.0001),
                        TextInput::make('unit_cost')->numeric()->minValue(0),
                        Select::make('reason')
                            ->options(['Opening inventory' => 'Opening inventory'])
                            ->default('Opening inventory')
                            ->required(),
                        Textarea::make('notes')->rows(3),
                    ])
                    ->action(function (Product $record, array $data, ListInventoryOverview $livewire): void {
                        app(InventoryService::class)->setOpeningStock(
                            $record->company_id,
                            static::selectedWarehouseId($livewire),
                            $record->id,
                            $data['opening_quantity'],
                            isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                            auth()->id(),
                            null,
                            trim(implode("\n\n", array_filter([$data['reason'] ?? null, $data['notes'] ?? null])))
                        );

                        Notification::make()->title('Opening stock recorded.')->success()->send();
                    }),
                Action::make('adjustStock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (): bool => auth()->user()?->can('inventory.adjust') ?? false)
                    ->schema([
                        Placeholder::make('warehouse')->content(fn (ListInventoryOverview $livewire): string => static::selectedWarehouseName($livewire)),
                        Placeholder::make('current_stock')->content(fn (Product $record): string => number_format((float) $record->current_quantity, 4, '.', '')),
                        TextInput::make('actual_quantity')->required()->numeric()->minValue(0),
                        Select::make('reason')
                            ->options([
                                'Physical count correction' => 'Physical count correction',
                                'Stock found' => 'Stock found',
                                'Missing stock' => 'Missing stock',
                                'Data correction' => 'Data correction',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Textarea::make('notes')->rows(3),
                    ])
                    ->action(function (Product $record, array $data, ListInventoryOverview $livewire): void {
                        $movement = app(InventoryService::class)->adjust(
                            $record->company_id,
                            static::selectedWarehouseId($livewire),
                            $record->id,
                            $data['actual_quantity'],
                            auth()->id(),
                            $data['reason'],
                            $data['notes'] ?? null,
                        );

                        Notification::make()
                            ->title($movement ? 'Stock adjustment recorded.' : 'Stock is already at that quantity.')
                            ->success()
                            ->send();
                    }),
                Action::make('recordDamage')
                    ->label('Record Damage')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn (): bool => auth()->user()?->can('inventory.damage') ?? false)
                    ->schema([
                        Placeholder::make('warehouse')->content(fn (ListInventoryOverview $livewire): string => static::selectedWarehouseName($livewire)),
                        Placeholder::make('current_stock')->content(fn (Product $record): string => number_format((float) $record->current_quantity, 4, '.', '')),
                        TextInput::make('quantity')->required()->numeric()->minValue(0.0001),
                        Select::make('reason')
                            ->options([
                                'Damaged in storage' => 'Damaged in storage',
                                'Expired or spoiled' => 'Expired or spoiled',
                                'Broken packaging' => 'Broken packaging',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Textarea::make('notes')->rows(3),
                    ])
                    ->action(function (Product $record, array $data, ListInventoryOverview $livewire): void {
                        app(InventoryService::class)->recordDamage(
                            $record->company_id,
                            static::selectedWarehouseId($livewire),
                            $record->id,
                            $data['quantity'],
                            auth()->id(),
                            $data['reason'],
                            $data['notes'] ?? null,
                        );

                        Notification::make()->title('Damage recorded.')->success()->send();
                    }),
                Action::make('recordLoss')
                    ->label('Record Loss')
                    ->icon('heroicon-o-minus-circle')
                    ->visible(fn (): bool => auth()->user()?->can('inventory.loss') ?? false)
                    ->schema([
                        Placeholder::make('warehouse')->content(fn (ListInventoryOverview $livewire): string => static::selectedWarehouseName($livewire)),
                        Placeholder::make('current_stock')->content(fn (Product $record): string => number_format((float) $record->current_quantity, 4, '.', '')),
                        TextInput::make('quantity')->required()->numeric()->minValue(0.0001),
                        Select::make('reason')
                            ->options([
                                'Missing during physical count' => 'Missing during physical count',
                                'Shrinkage' => 'Shrinkage',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Textarea::make('notes')->rows(3),
                    ])
                    ->action(function (Product $record, array $data, ListInventoryOverview $livewire): void {
                        app(InventoryService::class)->recordLoss(
                            $record->company_id,
                            static::selectedWarehouseId($livewire),
                            $record->id,
                            $data['quantity'],
                            auth()->id(),
                            $data['reason'],
                            $data['notes'] ?? null,
                        );

                        Notification::make()->title('Loss recorded.')->success()->send();
                    }),
                Action::make('viewMovements')
                    ->label('View Movements')
                    ->url(fn (Product $record, ListInventoryOverview $livewire): string => StockMovementResource::getUrl('index', [
                        'filters' => [
                            'warehouse_id' => ['value' => static::selectedWarehouseId($livewire)],
                            'product_id' => ['value' => $record->id],
                            'type' => ['value' => null],
                        ],
                    ]))
                    ->visible(fn (): bool => auth()->user()?->can('inventory.history') ?? false),
            ])
            ->defaultSort('products.name')
            ->paginated([25, 50, 100]);
    }

    private static function selectedWarehouseId(?ListInventoryOverview $livewire = null): string
    {
        $warehouseId = $livewire?->getSelectedWarehouseId();

        return AdminSupport::resolveAuthorizedWarehouseId($warehouseId) ?? throw new \RuntimeException('Warehouse context is required.');
    }

    private static function selectedWarehouseName(?ListInventoryOverview $livewire = null): string
    {
        return AdminSupport::authorizedWarehouseQuery()
            ->whereKey(static::selectedWarehouseId($livewire))
            ->value('name') ?? 'Unknown Warehouse';
    }
}
