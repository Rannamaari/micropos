<?php

namespace App\Filament\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('city')->toggleable(),
                TextColumn::make('currency')->label('Primary')->badge(),
                TextColumn::make('secondary_currency')->label('Accepted')->badge()->placeholder('—'),
                TextColumn::make('secondary_currency_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state, $record): string => $state
                        ? "1 {$record->currency} = ".number_format((float) $state, 4)." {$record->secondary_currency}"
                        : '—'),
                TextColumn::make('warehouses_count')->counts('warehouses')->label('Warehouses'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('is_active')->options([1 => 'Active', 0 => 'Inactive']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
