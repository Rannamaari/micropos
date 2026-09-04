<?php

namespace App\Filament\Resources\CashierShifts\Tables;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashierShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shift_number')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->toggleable(),
                TextColumn::make('cashier.name')->label('Cashier')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('opening_cash')->money(fn ($record): string => $record->currency)->sortable(),
                TextColumn::make('cash_variance')->money(fn ($record): string => $record->currency)->sortable(),
                TextColumn::make('opened_at')->dateTime()->sortable(),
                TextColumn::make('closed_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['open' => 'Open', 'closed' => 'Closed']),
            ])
            ->recordActions([
                Action::make('printEod')
                    ->label('Print A4')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record): string => route('cashier-shifts.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => $record->status === 'closed'),
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
