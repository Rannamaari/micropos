<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Support\AdminSupport;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')->searchable()->sortable(),
                TextColumn::make('sale_date')->date()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->badge()->sortable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->toggleable(),
                TextColumn::make('customer.name')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('grand_total')->formatStateUsing(fn ($state, $record): string => "{$record->currency} ".number_format((float) $state, 2))->sortable(),
                TextColumn::make('paid_total')->formatStateUsing(fn ($state, $record): string => "{$record->currency} ".number_format((float) $state, 2))->sortable(),
                TextColumn::make('payment_methods')
                    ->label('Payment Method')
                    ->state(fn ($record): string => $record->payments
                        ->filter(fn ($payment): bool => filled($payment->payment_method))
                        ->map(fn ($payment): string => ucwords(str_replace('_', ' ', $payment->payment_method))." ({$payment->currency})")
                        ->unique()
                        ->implode(', ') ?: '—'),
                TextColumn::make('balance_due')->formatStateUsing(fn ($state, $record): string => "{$record->currency} ".number_format((float) $state, 2))->sortable(),
                TextColumn::make('creator.name')->label('Cashier')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => AdminSupport::branchOptions())
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'held' => 'Held',
                        'completed' => 'Completed',
                        'voided' => 'Voided',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'credit' => 'Credit',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $method): Builder => $query->whereHas('payments', fn (Builder $payments): Builder => $payments->where('payment_method', $method)),
                    )),
                Filter::make('sale_date')
                    ->form([
                        DatePicker::make('date_from')->label('From'),
                        DatePicker::make('date_to')->label('To'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
                        ->when($data['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
