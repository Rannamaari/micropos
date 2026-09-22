<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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
                                Select::make('currency')->label('Primary currency')->options([
                                    'MVR' => 'MVR - Maldivian Rufiyaa',
                                    'USD' => 'USD - US Dollar',
                                ])->required()
                                    ->default('USD')
                                    ->live()
                                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('secondary_currency', $state === 'USD' ? 'MVR' : 'USD'))
                                    ->helperText('Changing the currency does not convert existing product prices. Review this branch’s store prices before selling.'),
                                Select::make('secondary_currency')
                                    ->label('Accepted secondary currency')
                                    ->options(fn (Get $get): array => $get('currency') === 'USD'
                                        ? ['MVR' => 'MVR - Maldivian Rufiyaa']
                                        : ['USD' => 'USD - US Dollar'])
                                    ->placeholder('Do not accept another currency')
                                    ->default('MVR')
                                    ->live(),
                                TextInput::make('secondary_currency_rate')
                                    ->label('Exchange rate')
                                    ->numeric()
                                    ->minValue(0.00000001)
                                    ->required(fn (Get $get): bool => filled($get('secondary_currency')))
                                    ->visible(fn (Get $get): bool => filled($get('secondary_currency')))
                                    ->helperText(fn (Get $get): string => filled($get('secondary_currency'))
                                        ? "Enter how many {$get('secondary_currency')} equal 1 {$get('currency')}. Example: 1 USD = 15.42 MVR."
                                        : 'Choose a secondary currency to configure its rate.'),
                                TextInput::make('address')->maxLength(65535)->columnSpanFull(),
                                Toggle::make('is_active')->default(true)->inline(false),
                            ]),
                    ]),
            ]);
    }
}
