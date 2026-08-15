<?php

namespace App\Filament\Resources\StockCounts\Pages;

use App\Filament\Resources\StockCounts\StockCountResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewStockCount extends ViewRecord
{
    protected static string $resource = StockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('countStock')
                ->label('Open Counting Worksheet')
                ->url(fn (): string => StockCountResource::getUrl('count', ['record' => $this->record])),
        ];
    }
}
