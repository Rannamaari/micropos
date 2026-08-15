<?php

namespace App\Filament\Resources\StockCounts;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\StockCounts\Pages\CountStock;
use App\Filament\Resources\StockCounts\Pages\CreateStockCount;
use App\Filament\Resources\StockCounts\Pages\ListStockCounts;
use App\Filament\Resources\StockCounts\Pages\ViewStockCount;
use App\Filament\Resources\StockCounts\Schemas\StockCountForm;
use App\Filament\Resources\StockCounts\Schemas\StockCountInfolist;
use App\Filament\Resources\StockCounts\Tables\StockCountsTable;
use App\Models\StockCount;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StockCountResource extends BaseResource
{
    protected static ?string $model = StockCount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $viewPermission = 'inventory.count';

    public static function form(Schema $schema): Schema
    {
        return StockCountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockCountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockCountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['warehouse', 'creator', 'completer'])
            ->withCount('items');
    }

    public static function canCreate(): bool
    {
        return static::hasPermission('inventory.count');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockCounts::route('/'),
            'create' => CreateStockCount::route('/create'),
            'count' => CountStock::route('/{record}/count'),
            'view' => ViewStockCount::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Inventory';
    }

    public static function getNavigationLabel(): string
    {
        return 'Stock Counts';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }
}
