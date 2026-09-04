<?php

namespace App\Filament\Resources\CashierShifts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashierShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shift reconciliation')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('shift_number'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('cashier.name')->label('Cashier'),
                    TextEntry::make('branch.name')->label('Branch'),
                    TextEntry::make('warehouse.name')->label('Warehouse'),
                    TextEntry::make('opened_at')->dateTime(),
                    TextEntry::make('opening_cash')->money(fn ($record): string => $record->currency),
                    TextEntry::make('expected_cash')->money(fn ($record): string => $record->currency),
                    TextEntry::make('closing_cash')->money(fn ($record): string => $record->currency),
                    TextEntry::make('cash_variance')->money(fn ($record): string => $record->currency),
                    TextEntry::make('closed_at')->dateTime(),
                    TextEntry::make('closing_notes')->columnSpanFull(),
                ]),
            ]),
        ]);
    }
}
