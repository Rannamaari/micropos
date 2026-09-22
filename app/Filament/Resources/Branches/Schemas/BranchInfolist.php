<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Branch')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('code'),
                                TextEntry::make('phone'),
                                TextEntry::make('email'),
                                TextEntry::make('city'),
                                TextEntry::make('currency')->label('Primary Currency')->badge(),
                                TextEntry::make('secondary_currency')->label('Secondary Currency')->badge()->placeholder('Not configured'),
                                TextEntry::make('secondary_currency_rate')
                                    ->label('Exchange Rate')
                                    ->formatStateUsing(fn ($state, $record): string => $state
                                        ? "1 {$record->currency} = ".number_format((float) $state, 4)." {$record->secondary_currency}"
                                        : 'Not configured'),
                                IconEntry::make('is_active')->boolean(),
                                TextEntry::make('address')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
