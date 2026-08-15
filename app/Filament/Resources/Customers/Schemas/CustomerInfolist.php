<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('code'),
                                TextEntry::make('name'),
                                TextEntry::make('phone'),
                                TextEntry::make('email'),
                                TextEntry::make('credit_limit'),
                                TextEntry::make('opening_balance'),
                                IconEntry::make('is_walk_in')->boolean(),
                                IconEntry::make('is_active')->boolean(),
                                TextEntry::make('address')->columnSpanFull(),
                                TextEntry::make('notes')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
