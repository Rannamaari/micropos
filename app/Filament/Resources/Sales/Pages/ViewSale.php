<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('thermalReceipt')
                ->label('Reprint Thermal Receipt')
                ->icon('heroicon-o-printer')
                ->url(fn (Sale $record): string => route('admin.sales.receipt', ['sale' => $record, 'format' => 'thermal']))
                ->openUrlInNewTab(),
            Action::make('a4Invoice')
                ->label('Reprint A4 Tax Invoice')
                ->icon('heroicon-o-document-text')
                ->url(fn (Sale $record): string => route('admin.sales.receipt', ['sale' => $record, 'format' => 'a4']))
                ->openUrlInNewTab(),
        ];
    }
}
