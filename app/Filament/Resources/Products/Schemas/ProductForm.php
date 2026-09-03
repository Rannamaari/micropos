<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\AdminSupport;
use App\Filament\Resources\InventoryOverview\InventoryOverviewResource;
use App\Models\Product;
use App\Models\Branch;
use App\Services\InventoryQueryService;
use App\Support\InventoryStatus;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')
                    ->default(fn (): ?string => AdminSupport::companyId()),
                Section::make('Product Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sku')
                                    ->required()
                                    ->maxLength(255)
                                    ->rule(fn () => Rule::unique('products', 'sku')
                                        ->where('company_id', AdminSupport::companyId())
                                        ->ignore(request()->route('record'))),
                                Select::make('category_id')
                                    ->options(fn (): array => AdminSupport::categoryOptions())
                                    ->searchable()
                                    ->preload(),
                                Select::make('brand_id')
                                    ->options(fn (): array => AdminSupport::brandOptions())
                                    ->searchable()
                                    ->preload(),
                                Select::make('unit_id')
                                    ->required()
                                    ->options(fn (): array => AdminSupport::unitOptions())
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
                Section::make('Pricing')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('cost_price')->numeric()->minValue(0)->default(0),
                                TextInput::make('selling_price')->numeric()->minValue(0)->required(),
                                TextInput::make('wholesale_price')->numeric()->minValue(0),
                                TextInput::make('tax_rate')->numeric()->minValue(0)->default(0),
                                TextInput::make('minimum_stock')->numeric()->minValue(0)->default(0),
                            ]),
                    ]),
                Section::make('Store Prices')
                    ->description('Set the selling and cost price for each store. POS always uses the price for its assigned store.')
                    ->schema([
                        Repeater::make('branchPrices')
                            ->relationship()
                            ->reorderable(false)
                            ->schema([
                                Select::make('branch_id')
                                    ->required()
                                    ->options(fn (): array => Branch::query()->where('company_id', AdminSupport::companyId())->orderBy('name')->pluck('name', 'id')->all()),
                                Select::make('currency')->required()->options(['MVR' => 'MVR', 'USD' => 'USD']),
                                TextInput::make('cost_price')->numeric()->minValue(0)->default(0)->required(),
                                TextInput::make('selling_price')->numeric()->minValue(0)->required(),
                                TextInput::make('wholesale_price')->numeric()->minValue(0),
                                Hidden::make('company_id')->default(fn (): ?string => AdminSupport::companyId()),
                            ])->columns(5),
                    ]),
                Section::make('Inventory Rules')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('track_inventory')
                                    ->default(true)
                                    ->inline(false),
                                Toggle::make('allow_negative_stock')
                                    ->default(false)
                                    ->inline(false),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Barcodes')
                    ->description('Use one primary barcode for scanner lookup and keep any alternate barcodes on the same product.')
                    ->schema([
                        Repeater::make('barcodes')
                            ->relationship()
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->schema([
                                TextInput::make('barcode')
                                    ->required()
                                    ->maxLength(255),
                                Toggle::make('is_primary')
                                    ->label('Primary')
                                    ->default(false)
                                    ->inline(false),
                                Hidden::make('company_id')
                                    ->default(fn (): ?string => AdminSupport::companyId()),
                            ])
                            ->columns(2),
                    ]),
                Section::make('Inventory')
                    ->visible(fn (?Product $record): bool => (bool) $record?->exists)
                    ->schema([
                        ViewField::make('inventory_snapshot')
                            ->view('filament.products.inventory-snapshot')
                            ->dehydrated(false)
                            ->viewData(fn (?Product $record): array => static::inventoryViewData($record)),
                    ]),
            ]);
    }

    private static function inventoryViewData(?Product $record): array
    {
        if (! $record) {
            return [
                'trackInventory' => false,
                'rows' => [],
                'totalQuantity' => '0.0000',
                'inventoryOverviewUrl' => null,
            ];
        }

        $rows = app(InventoryQueryService::class)
            ->productInventorySnapshot($record->company_id, $record->id)
            ->map(function (array $row) use ($record): array {
                $quantity = (float) $row['current_quantity'];

                return [
                    ...$row,
                    'status' => InventoryStatus::forProduct((bool) $record->track_inventory, $quantity, (float) $record->minimum_stock),
                    'status_color' => InventoryStatus::color(InventoryStatus::forProduct((bool) $record->track_inventory, $quantity, (float) $record->minimum_stock)),
                    'inventory_url' => InventoryOverviewResource::getUrl('index', [
                        'search' => $record->sku,
                        'filters' => ['warehouse_id' => ['value' => $row['id']]],
                    ]),
                ];
            })
            ->all();

        return [
            'trackInventory' => (bool) $record->track_inventory,
            'rows' => $rows,
            'totalQuantity' => number_format(collect($rows)->sum(fn (array $row): float => (float) $row['current_quantity']), 4, '.', ''),
            'inventoryOverviewUrl' => InventoryOverviewResource::getUrl('index', ['search' => $record->sku]),
        ];
    }
}
