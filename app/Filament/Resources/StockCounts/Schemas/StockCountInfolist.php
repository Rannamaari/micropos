<?php

namespace App\Filament\Resources\StockCounts\Schemas;

use App\Models\StockCount;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class StockCountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stock Count')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('warehouse.name'),
                                TextEntry::make('creator.name'),
                                TextEntry::make('completer.name'),
                                TextEntry::make('started_at')->dateTime(),
                                TextEntry::make('completed_at')->dateTime(),
                                TextEntry::make('items_count')
                                    ->label('Worksheet Items'),
                                TextEntry::make('counted_items')
                                    ->label('Counted Items')
                                    ->state(fn (StockCount $record): int => $record->items()->whereNotNull('counted_quantity')->count()),
                                TextEntry::make('pending_items')
                                    ->label('Pending Items')
                                    ->state(fn (StockCount $record): int => $record->items()->whereNull('counted_quantity')->count()),
                                TextEntry::make('notes')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
