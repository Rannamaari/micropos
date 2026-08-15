<?php

namespace App\Filament\Resources\StockCounts\Tables;

use App\Enums\StockCountStatus;
use App\Filament\Resources\StockCounts\StockCountResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockCountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Reference'),
                TextColumn::make('warehouse.name')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('started_at')->dateTime()->sortable(),
                TextColumn::make('completed_at')->dateTime()->sortable(),
                TextColumn::make('creator.name')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('countStock')
                    ->label('Count Stock')
                    ->url(fn ($record): string => StockCountResource::getUrl('count', ['record' => $record]))
                    ->visible(fn ($record): bool => $record->status === StockCountStatus::InProgress),
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
