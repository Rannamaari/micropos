<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')->default(fn (): ?string => AdminSupport::companyId()),
                Section::make('Warehouse Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('branch_id')
                                    ->required()
                                    ->options(fn (): array => AdminSupport::branchOptions())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('name')->required()->maxLength(255),
                                TextInput::make('code')->required()->maxLength(255),
                                Toggle::make('is_default')->inline(false),
                                Toggle::make('is_active')->default(true)->inline(false),
                                TextInput::make('address')->maxLength(65535)->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
