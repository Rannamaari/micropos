<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movement Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('occurred_at')->dateTime(),
                                TextEntry::make('type')->badge(),
                                TextEntry::make('warehouse.name'),
                                TextEntry::make('product.name'),
                                TextEntry::make('product.sku')->label('SKU'),
                                TextEntry::make('product.primaryBarcode.barcode')->label('Primary Barcode'),
                                TextEntry::make('quantity'),
                                TextEntry::make('quantity_before'),
                                TextEntry::make('quantity_after'),
                                TextEntry::make('reference_number')->label('Reference'),
                                TextEntry::make('creator.name')->label('User'),
                                TextEntry::make('reason'),
                                TextEntry::make('notes')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
