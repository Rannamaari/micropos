<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->maxLength(255)
                                    ->rule(fn () => Rule::unique('brands', 'code')
                                        ->where('company_id', AdminSupport::companyId())
                                        ->ignore(request()->route('record'))),
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(1),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
