<?php

namespace App\Filament\Resources\CashierShifts\Pages;

use App\Filament\Resources\CashierShifts\CashierShiftResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewCashierShift extends ViewRecord
{
    protected static string $resource = CashierShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printEod')
                ->label('Print A4 EOD')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('cashier-shifts.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->status === 'closed'),
        ];
    }
}
