<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class WarehouseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Warehouse')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('code'),
                                TextEntry::make('branch.name'),
                                IconEntry::make('is_default')->boolean(),
                                IconEntry::make('is_active')->boolean(),
                                TextEntry::make('address')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
