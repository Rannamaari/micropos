<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sale')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('sale_number'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('sale_date')->date(),
                                TextEntry::make('customer.name'),
                                TextEntry::make('branch.name'),
                                TextEntry::make('warehouse.name'),
                                TextEntry::make('grand_total')->money(fn ($record): string => $record->currency),
                                TextEntry::make('paid_total')->money(fn ($record): string => $record->currency),
                                TextEntry::make('balance_due')->money(fn ($record): string => $record->currency),
                                TextEntry::make('creator.name'),
                                TextEntry::make('receipt_print_events_count')->label('Admin Reprints'),
                                TextEntry::make('completed_at')->dateTime(),
                                TextEntry::make('notes')->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Items')
                    ->schema([
                        TextEntry::make('items.product.name')
                            ->label('Products')
                            ->listWithLineBreaks(),
                        TextEntry::make('items.quantity')
                            ->label('Quantities')
                            ->listWithLineBreaks(),
                        TextEntry::make('payments.payment_method')
                            ->label('Payments')
                            ->listWithLineBreaks(),
                        TextEntry::make('payments.currency')
                            ->label('Payment Currencies')
                            ->listWithLineBreaks(),
                        TextEntry::make('payments.currency_amount')
                            ->label('Native Payment Amounts')
                            ->listWithLineBreaks(),
                        TextEntry::make('payments.amount')
                            ->label('Applied in Sale Currency')
                            ->listWithLineBreaks(),
                    ]),
            ]);
    }
}
