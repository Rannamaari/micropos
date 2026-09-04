<?php

namespace App\Filament\Resources\CashierShifts;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\CashierShifts\Pages\ListCashierShifts;
use App\Filament\Resources\CashierShifts\Pages\ViewCashierShift;
use App\Filament\Resources\CashierShifts\Schemas\CashierShiftInfolist;
use App\Filament\Resources\CashierShifts\Tables\CashierShiftsTable;
use App\Models\CashierShift;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CashierShiftResource extends BaseResource
{
    protected static ?string $model = CashierShift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $recordTitleAttribute = 'shift_number';

    protected static ?string $viewPermission = 'reports.view';

    public static function infolist(Schema $schema): Schema
    {
        return CashierShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierShiftsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['branch', 'warehouse', 'cashier']);
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierShifts::route('/'),
            'view' => ViewCashierShift::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null { return 'Reports'; }
    public static function getNavigationLabel(): string { return 'Cashier Shifts / EOD'; }
    public static function getNavigationSort(): ?int { return 1; }
}
