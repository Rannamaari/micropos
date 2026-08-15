<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Purchase;
use App\Models\StockMovement;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class PurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('purchase_number'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('purchase_date')->date(),
                                TextEntry::make('supplier.name'),
                                TextEntry::make('supplier_invoice_number')->label('Supplier Invoice'),
                                TextEntry::make('branch.name'),
                                TextEntry::make('warehouse.name'),
                                TextEntry::make('expected_date')->date(),
                                TextEntry::make('creator.name')->label('Created By'),
                                TextEntry::make('grand_total')->money('MVR'),
                                TextEntry::make('paid_total')->money('MVR'),
                                TextEntry::make('balance_due')->money('MVR'),
                                TextEntry::make('receiver.name')->label('Last Received By'),
                                TextEntry::make('received_at')->dateTime(),
                                TextEntry::make('notes')->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Items')
                    ->schema([
                        TextEntry::make('items.product.name')
                            ->label('Products')
                            ->listWithLineBreaks(),
                        TextEntry::make('items.product.sku')
                            ->label('SKU')
                            ->listWithLineBreaks(),
                        TextEntry::make('items.ordered_quantity')
                            ->label('Ordered')
                            ->listWithLineBreaks(),
                        TextEntry::make('items.received_quantity')
                            ->label('Received')
                            ->listWithLineBreaks(),
                        TextEntry::make('remaining_quantity')
                            ->label('Remaining')
                            ->state(fn (Purchase $record): array => $record->items->map(fn ($item) => number_format(max(0, (float) $item->ordered_quantity - (float) $item->received_quantity), 4, '.', ''))->all())
                            ->listWithLineBreaks(),
                        TextEntry::make('items.unit_cost')
                            ->label('Unit Cost')
                            ->listWithLineBreaks(),
                        TextEntry::make('items.line_total')
                            ->label('Line Total')
                            ->listWithLineBreaks(),
                    ]),
                Section::make('Receipt History')
                    ->schema([
                        TextEntry::make('receipt_history')
                            ->state(fn (Purchase $record): array => StockMovement::query()
                                ->with(['product', 'creator'])
                                ->where('reference_type', Purchase::class)
                                ->where('reference_id', $record->id)
                                ->orderBy('occurred_at')
                                ->get()
                                ->map(fn (StockMovement $movement): string => sprintf(
                                    '%s • %s • %s +%s',
                                    $movement->occurred_at?->format('d M H:i') ?? 'Unknown time',
                                    $movement->creator?->name ?? 'System',
                                    $movement->product?->name ?? 'Product',
                                    number_format((float) $movement->quantity, 4, '.', ''),
                                ))
                                ->all())
                            ->listWithLineBreaks(),
                    ])
                    ->visible(fn (Purchase $record): bool => StockMovement::query()
                        ->where('reference_type', Purchase::class)
                        ->where('reference_id', $record->id)
                        ->exists()),
                Section::make('Payments')
                    ->schema([
                        TextEntry::make('payments_summary')
                            ->state(fn (Purchase $record): array => $record->payments
                                ->sortBy('paid_at')
                                ->map(fn ($payment): string => sprintf(
                                    '%s • %s • MVR %s%s',
                                    $payment->paid_at?->format('d M Y') ?? 'Unknown date',
                                    strtoupper((string) $payment->payment_method),
                                    number_format((float) $payment->amount, 4, '.', ''),
                                    $payment->reference ? ' • '.$payment->reference : '',
                                ))
                                ->values()
                                ->all())
                            ->listWithLineBreaks(),
                    ])
                    ->visible(fn (Purchase $record): bool => $record->payments->isNotEmpty()),
                Section::make('Returns')
                    ->schema([
                        TextEntry::make('returns_summary')
                            ->state(fn (Purchase $record): array => $record->returns
                                ->sortBy('return_date')
                                ->map(fn ($return): string => sprintf(
                                    '%s • %s • MVR %s',
                                    optional($return->return_date)->format('d M Y') ?? 'Unknown date',
                                    $return->purchase_return_number,
                                    number_format((float) $return->grand_total, 4, '.', ''),
                                ))
                                ->values()
                                ->all())
                            ->listWithLineBreaks(),
                    ])
                    ->visible(fn (Purchase $record): bool => $record->returns->isNotEmpty()),
            ]);
    }
}
