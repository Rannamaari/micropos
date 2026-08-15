<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')->searchable()->sortable(),
                TextColumn::make('sale_date')->date()->sortable(),
                TextColumn::make('customer.name')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('grand_total')->money('MVR')->sortable(),
                TextColumn::make('paid_total')->money('MVR')->sortable(),
                TextColumn::make('balance_due')->money('MVR')->sortable(),
                TextColumn::make('creator.name')->label('Cashier')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'held' => 'Held',
                        'completed' => 'Completed',
                        'voided' => 'Voided',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
