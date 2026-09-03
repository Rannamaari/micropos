<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Enums\PurchaseStatus;
use App\Filament\Support\AdminSupport;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase Order')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->required()
                                    ->options(fn (): array => AdminSupport::supplierOptions())
                                    ->default(request()->query('supplier_id'))
                                    ->searchable()
                                    ->preload(),
                                Select::make('warehouse_id')
                                    ->label('Warehouse')
                                    ->required()
                                    ->options(fn (): array => AdminSupport::warehouseOptions())
                                    ->default(fn (): ?string => AdminSupport::resolveAuthorizedWarehouseId())
                                    ->searchable()
                                    ->disabled(fn (): bool => count(AdminSupport::warehouseOptions()) === 1)
                                    ->dehydrated(),
                                Select::make('status')
                                    ->required()
                                    ->default(PurchaseStatus::Ordered->value)
                                    ->options([
                                        PurchaseStatus::Draft->value => 'Save Draft',
                                        PurchaseStatus::Ordered->value => 'Save as Ordered',
                                    ]),
                                DatePicker::make('purchase_date')
                                    ->required()
                                    ->default(now()),
                                DatePicker::make('expected_date'),
                                TextInput::make('supplier_invoice_number')
                                    ->label('Supplier Invoice Number')
                                    ->maxLength(255),
                                TextInput::make('shipping_total')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('other_cost_total')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Textarea::make('notes')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Products')
                    ->schema([
                        Repeater::make('items')
                            ->label('Purchase Lines')
                            ->required()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Product')
                            ->reorderable(false)
                            ->schema([
                                Hidden::make('description'),
                                Grid::make(6)
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Product')
                                            ->required()
                                            ->searchable()
                                            ->getSearchResultsUsing(fn (string $search): array => static::searchProducts($search))
                                            ->getOptionLabelUsing(fn ($value): ?string => static::productLabel($value))
                                            ->afterStateUpdated(function ($state, $set): void {
                                                $product = $state ? Product::query()->with('primaryBarcode')->find($state) : null;

                                                $set('sku', $product?->sku);
                                                $set('description', $product?->name);
                                                $set('unit_cost', $product ? (float) $product->cost_price : 0);
                                                $set('tax_rate', $product ? (float) $product->tax_rate : 0);
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('sku')
                                            ->disabled()
                                            ->dehydrated(false),
                                        TextInput::make('ordered_quantity')
                                            ->label('Ordered Quantity')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0.0001),
                                        TextInput::make('unit_cost')
                                            ->label('Unit Cost')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0),
                                        TextInput::make('discount_amount')
                                            ->label('Discount')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
                                        TextInput::make('tax_rate')
                                            ->label('Tax %')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
                                        Placeholder::make('line_total_preview')
                                            ->label('Line Total')
                                            ->content(fn ($get): string => number_format(static::lineTotal($get), 4, '.', ''))
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->columns(1),
                    ]),
                Section::make('Totals Preview')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                Placeholder::make('subtotal_preview')
                                    ->label('Subtotal')
                                    ->content(fn ($get): string => number_format(static::totals($get)['subtotal'], 4, '.', '')),
                                Placeholder::make('discount_preview')
                                    ->label('Discount')
                                    ->content(fn ($get): string => number_format(static::totals($get)['discount_total'], 4, '.', '')),
                                Placeholder::make('tax_preview')
                                    ->label('Tax')
                                    ->content(fn ($get): string => number_format(static::totals($get)['tax_total'], 4, '.', '')),
                                Placeholder::make('other_preview')
                                    ->label('Shipping + Other')
                                    ->content(fn ($get): string => number_format(static::totals($get)['other_total'], 4, '.', '')),
                                Placeholder::make('grand_total_preview')
                                    ->label('Grand Total')
                                    ->content(fn ($get): string => new HtmlString('<strong>'.number_format(static::totals($get)['grand_total'], 4, '.', '').'</strong>')),
                            ]),
                    ]),
            ]);
    }

    private static function searchProducts(string $search): array
    {
        $term = trim($search);

        if ($term === '' || ! AdminSupport::companyId()) {
            return [];
        }

        return Product::query()
            ->with('primaryBarcode')
            ->where('company_id', AdminSupport::companyId())
            ->where('is_active', true)
            ->where(function ($query) use ($term): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $query->whereLike('name', $like, caseSensitive: false)
                    ->orWhereLike('sku', $like, caseSensitive: false)
                    ->orWhereHas('barcodes', fn ($barcodeQuery) => $barcodeQuery->whereLike('barcode', $like, caseSensitive: false));
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => sprintf('%s (%s%s)', $product->name, $product->sku, $product->primaryBarcode?->barcode ? ' • '.$product->primaryBarcode->barcode : ''),
            ])
            ->all();
    }

    private static function productLabel(?string $productId): ?string
    {
        if (! $productId) {
            return null;
        }

        $product = Product::query()->with('primaryBarcode')->find($productId);

        if (! $product) {
            return null;
        }

        return sprintf('%s (%s%s)', $product->name, $product->sku, $product->primaryBarcode?->barcode ? ' • '.$product->primaryBarcode->barcode : '');
    }

    private static function lineTotal($get): float
    {
        $quantity = (float) ($get('ordered_quantity') ?: 0);
        $unitCost = (float) ($get('unit_cost') ?: 0);
        $discount = (float) ($get('discount_amount') ?: 0);
        $taxRate = (float) ($get('tax_rate') ?: 0);
        $subtotal = $quantity * $unitCost;
        $taxBase = max(0, $subtotal - $discount);

        return round($taxBase + ($taxBase * ($taxRate / 100)), 4);
    }

    /**
     * @return array{subtotal:float,discount_total:float,tax_total:float,other_total:float,grand_total:float}
     */
    private static function totals($get): array
    {
        $items = $get('items') ?? [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $quantity = (float) ($item['ordered_quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $discount = (float) ($item['discount_amount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);
            $lineSubtotal = round($quantity * $unitCost, 4);
            $lineTaxBase = max(0, $lineSubtotal - $discount);
            $lineTax = round($lineTaxBase * ($taxRate / 100), 4);

            $subtotal += $lineSubtotal;
            $discountTotal += $discount;
            $taxTotal += $lineTax;
        }

        $other = (float) ($get('shipping_total') ?: 0) + (float) ($get('other_cost_total') ?: 0);

        return [
            'subtotal' => round($subtotal, 4),
            'discount_total' => round($discountTotal, 4),
            'tax_total' => round($taxTotal, 4),
            'other_total' => round($other, 4),
            'grand_total' => round($subtotal - $discountTotal + $taxTotal + $other, 4),
        ];
    }
}
