<?php

namespace App\Filament\Resources\InventoryOverview;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\InventoryOverview\Pages\ListInventoryOverview;
use App\Filament\Resources\InventoryOverview\Tables\InventoryOverviewTable;
use App\Models\Product;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InventoryOverviewResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $viewPermission = 'inventory.view';

    protected static ?string $slug = 'inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return InventoryOverviewTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryOverview::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Inventory Overview';
    }

    public static function getModelLabel(): string
    {
        return 'Inventory Overview';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Inventory Overview';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Inventory';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
