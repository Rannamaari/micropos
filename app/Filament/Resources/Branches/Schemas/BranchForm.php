<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')->default(fn (): ?string => AdminSupport::companyId()),
                Section::make('Branch Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')->required()->maxLength(255),
                                TextInput::make('code')->required()->maxLength(255),
                                TextInput::make('phone')->maxLength(255),
                                TextInput::make('email')->email()->maxLength(255),
                                TextInput::make('city')->maxLength(255),
                                TextInput::make('address')->maxLength(65535)->columnSpanFull(),
                                Toggle::make('is_active')->default(true)->inline(false),
                            ]),
                    ]),
            ]);
    }
}
