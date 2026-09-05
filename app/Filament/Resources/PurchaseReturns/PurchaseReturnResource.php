<?php

namespace App\Filament\Resources\PurchaseReturns;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Resources\PurchaseReturns\Pages\ViewPurchaseReturn;
use App\Models\PurchaseReturn;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseReturnResource extends BaseResource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?string $recordTitleAttribute = 'purchase_return_number';

    protected static ?string $viewPermission = 'purchases.return';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Purchase Return')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('purchase_return_number'),
                        TextEntry::make('return_date')->date(),
                        TextEntry::make('supplier.name'),
                        TextEntry::make('purchase.purchase_number')->label('Purchase Order'),
                        TextEntry::make('warehouse.name'),
                        TextEntry::make('grand_total')->money('MVR'),
                        TextEntry::make('notes')->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_return_number')->searchable()->sortable(),
                TextColumn::make('return_date')->date()->sortable(),
                TextColumn::make('purchase.purchase_number')->label('Purchase Order')->searchable(),
                TextColumn::make('supplier.name')->searchable(),
                TextColumn::make('warehouse.name')->sortable(),
                TextColumn::make('grand_total')->money('MVR')->sortable(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['purchase', 'supplier', 'warehouse', 'items']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseReturns::route('/'),
            'view' => ViewPurchaseReturn::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('nav.purchasing');
    }
}
