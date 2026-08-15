<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Services\SupplierLedgerService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Supplier')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('code'),
                                TextEntry::make('name'),
                                TextEntry::make('contact_person'),
                                TextEntry::make('phone'),
                                TextEntry::make('email'),
                                TextEntry::make('credit_limit'),
                                TextEntry::make('opening_balance'),
                                TextEntry::make('payment_terms_days'),
                                TextEntry::make('current_payable')
                                    ->state(fn ($record): string => app(SupplierLedgerService::class)->currentPayable($record->id))
                                    ->label('Current Payable'),
                                TextEntry::make('purchases_count')
                                    ->state(fn ($record): int => $record->purchases()->count())
                                    ->label('Purchases'),
                                TextEntry::make('returns_count')
                                    ->state(fn ($record): int => $record->returns()->count())
                                    ->label('Returns'),
                                TextEntry::make('payments_count')
                                    ->state(fn ($record): int => $record->payments()->count())
                                    ->label('Payments'),
                                IconEntry::make('is_active')->boolean(),
                                TextEntry::make('address')->columnSpanFull(),
                                TextEntry::make('notes')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
