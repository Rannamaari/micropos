<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use App\Filament\Support\AdminSupport;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->label('Date / Time')->dateTime()->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable(),
                TextColumn::make('product.sku')->label('SKU')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                TextColumn::make('type')->label('Movement Type')->badge()->sortable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('quantity_before')->label('Before')->sortable(),
                TextColumn::make('quantity_after')->label('After')->sortable(),
                TextColumn::make('reference_number')->label('Reference')->toggleable(),
                TextColumn::make('creator.name')->label('User')->toggleable(),
                TextColumn::make('reason')->toggleable(),
                TextColumn::make('notes')->toggleable(isToggledHiddenByDefault: true)->limit(40),
            ])
            ->filters([
                Filter::make('occurred_between')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('occurred_at', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('occurred_at', '<=', $date));
                    }),
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn (): array => AdminSupport::warehouseOptions()),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn (): array => AdminSupport::productOptions(true))
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Movement Type')
                    ->options(collect(StockMovementType::cases())->mapWithKeys(fn (StockMovementType $type): array => [$type->value => strtoupper(str_replace('_', ' ', $type->value))])->all()),
                SelectFilter::make('created_by')
                    ->label('User')
                    ->relationship('creator', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
