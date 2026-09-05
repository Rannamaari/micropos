<?php

namespace App\Filament\Resources\Purchases;

use App\Enums\PurchaseStatus;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\Pages\ViewPurchase;
use App\Filament\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Resources\Purchases\Schemas\PurchaseInfolist;
use App\Filament\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Purchase;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseResource extends BaseResource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $recordTitleAttribute = 'purchase_number';

    protected static ?string $viewPermission = 'purchases.view';

    protected static ?string $createPermission = 'purchases.create';

    protected static ?string $updatePermission = 'purchases.create';

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'branch', 'warehouse', 'items.product.primaryBarcode', 'payments', 'creator', 'receiver']);
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof Purchase) {
            return false;
        }

        if (! parent::canEdit($record)) {
            return false;
        }

        if (! in_array($record->status, [PurchaseStatus::Draft, PurchaseStatus::Ordered], true)) {
            return false;
        }

        if ((float) $record->paid_total > 0.0001) {
            return false;
        }

        return ! $record->items()->where('received_quantity', '>', 0)->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'view' => ViewPurchase::route('/{record}'),
            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('nav.purchasing');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.purchase_orders');
    }
}
