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
                    TextEntry::make('opening_cash_by_currency')
                        ->label('Opening Cash by Currency')
                        ->formatStateUsing(fn (?array $state): string => collect($state ?? [])->map(fn ($amount, $currency): string => "{$currency} ".number_format((float) $amount, 2))->implode(' · ')),
                    TextEntry::make('expected_cash_by_currency')
                        ->label('Expected Cash by Currency')
                        ->formatStateUsing(fn (?array $state): string => collect($state ?? [])->map(fn ($amount, $currency): string => "{$currency} ".number_format((float) $amount, 2))->implode(' · ')),
                    TextEntry::make('closing_cash_by_currency')
                        ->label('Counted Cash by Currency')
                        ->formatStateUsing(fn (?array $state): string => collect($state ?? [])->map(fn ($amount, $currency): string => "{$currency} ".number_format((float) $amount, 2))->implode(' · ')),
                    TextEntry::make('cash_variance_by_currency')
                        ->label('Variance by Currency')
                        ->formatStateUsing(fn (?array $state): string => collect($state ?? [])->map(fn ($amount, $currency): string => "{$currency} ".number_format((float) $amount, 2))->implode(' · ')),
                    TextEntry::make('closed_at')->dateTime(),
                    TextEntry::make('closing_notes')->columnSpanFull(),
                ]),
            ]),
        ]);
    }
}
