<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('code'),
                                TextEntry::make('parent.name')->label('Parent'),
                                IconEntry::make('is_active')->boolean(),
                                TextEntry::make('description')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
