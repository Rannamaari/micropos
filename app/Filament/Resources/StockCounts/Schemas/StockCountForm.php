<?php

namespace App\Filament\Resources\StockCounts\Schemas;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class StockCountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('New Stock Count')
                    ->description('Create a counting worksheet for one warehouse. Staff can then search products and enter the physical quantity they counted.')
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->options(fn (): array => AdminSupport::warehouseOptions())
                            ->default(fn (): ?string => AdminSupport::resolveAuthorizedWarehouseId())
                            ->required()
                            ->searchable()
                            ->preload(),
                        Placeholder::make('scope')
                            ->label('Count Scope')
                            ->content('All active inventory-tracked products in the selected warehouse will be included in the count.'),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4),
                    ]),
            ]);
    }
}
