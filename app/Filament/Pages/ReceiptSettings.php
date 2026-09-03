<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminSupport;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class ReceiptSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = 'Receipt Settings';

    protected static ?string $title = 'Thermal Receipt Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected string $view = 'filament.pages.receipt-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $company = AdminSupport::company();

        abort_unless($company, 403, 'A company assignment is required to edit receipt settings.');

        $this->form->fill(Arr::only($company->toArray(), [
            'name', 'tax_number', 'address', 'city', 'country', 'phone',
            'receipt_shop_name', 'receipt_gst_label', 'receipt_header', 'receipt_footer',
            'receipt_show_address', 'receipt_show_phone',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Receipt Identity')
                    ->description('These details are printed at the top of every thermal receipt.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('receipt_shop_name')
                            ->label('Receipt Shop Name')
                            ->helperText('Leave blank to print the company name.')
                            ->maxLength(255),
                        TextInput::make('receipt_gst_label')
                            ->label('GST Label')
                            ->default('GST No.')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('tax_number')
                            ->label('GST Number')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Receipt Phone')
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Receipt Address')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextInput::make('city')->maxLength(255),
                            TextInput::make('country')->maxLength(255),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('receipt_show_address')->label('Print address')->default(true),
                            Toggle::make('receipt_show_phone')->label('Print phone')->default(true),
                        ]),
                    ]),
                Section::make('Receipt Messages')
                    ->schema([
                        Textarea::make('receipt_header')
                            ->label('Header Message')
                            ->helperText('Printed below the shop details.')
                            ->rows(3)
                            ->maxLength(1000),
                        Textarea::make('receipt_footer')
                            ->label('Footer Message')
                            ->helperText('Printed above “Powered by micronet.mv”.')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $company = AdminSupport::company();

        abort_unless($company, 403);
        $company->update($this->form->getState());

        Notification::make()->title('Receipt settings saved')->success()->send();
    }
}
