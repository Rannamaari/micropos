<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_number')->searchable()->sortable(),
                TextColumn::make('purchase_date')->date()->sortable(),
                TextColumn::make('supplier.name')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->sortable(),
                TextColumn::make('supplier_invoice_number')->label('Supplier Invoice')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('grand_total')->money('MVR')->sortable(),
                TextColumn::make('paid_total')->money('MVR')->sortable(),
                TextColumn::make('balance_due')->money('MVR')->sortable(),
                TextColumn::make('creator.name')->label('Created By')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn (): array => AdminSupport::supplierOptions()),
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn (): array => AdminSupport::warehouseOptions()),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'ordered' => 'Ordered',
                        'partially_received' => 'Partially Received',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('purchase_date')
                    ->form([
                        DatePicker::make('date_from'),
                        DatePicker::make('date_to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '>=', $date))
                            ->when($data['date_to'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->searchPlaceholder('Search purchase no., supplier invoice, or supplier name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $query): Builder {
                return $query;
            }))
            ->toolbarActions([]);
    }
}
