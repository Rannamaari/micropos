<?php

namespace App\Filament\Support;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminSupport
{
    public static function user(): ?User
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function companyId(): ?string
    {
        return static::user()?->company_id;
    }

    public static function isSuperAdmin(): bool
    {
        return (bool) static::user()?->hasRole('super-admin');
    }

    public static function company(): ?Company
    {
        $user = static::user();

        return $user?->company;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    public static function companyQuery(string $modelClass, string $column = 'name'): Builder
    {
        /** @var Builder<Model> $query */
        $query = $modelClass::query()->orderBy($column);

        if (! static::isSuperAdmin() && static::companyId()) {
            $query->where('company_id', static::companyId());
        }

        return $query;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    public static function companyOptions(string $modelClass, string $labelColumn = 'name', string $column = 'name'): array
    {
        return static::companyQuery($modelClass, $column)
            ->pluck($labelColumn, 'id')
            ->all();
    }

    public static function activeCompanyOptions(string $modelClass, string $labelColumn = 'name', string $column = 'name'): array
    {
        return static::companyQuery($modelClass, $column)
            ->where('is_active', true)
            ->pluck($labelColumn, 'id')
            ->all();
    }

    public static function categoryOptions(): array
    {
        return static::activeCompanyOptions(Category::class);
    }

    public static function brandOptions(): array
    {
        return static::activeCompanyOptions(Brand::class);
    }

    public static function unitOptions(): array
    {
        return Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function branchOptions(): array
    {
        return static::activeCompanyOptions(Branch::class);
    }

    public static function warehouseOptions(?string $branchId = null): array
    {
        $query = static::authorizedWarehouseQuery();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->all();
    }

    public static function activeWarehouseId(): ?string
    {
        $user = static::user();

        if ($user?->warehouse_id) {
            return $user->warehouse_id;
        }

        return static::authorizedWarehouseQuery()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->value('id');
    }

    /**
     * @return Builder<Warehouse>
     */
    public static function authorizedWarehouseQuery(): Builder
    {
        /** @var Builder<Warehouse> $query */
        $query = Warehouse::query()->orderBy('name');

        $user = static::user();

        if (! $user || static::isSuperAdmin()) {
            return $query;
        }

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        if ($user->warehouse_id) {
            $query->whereKey($user->warehouse_id);
        }

        return $query;
    }

    public static function canAccessWarehouse(?string $warehouseId): bool
    {
        if (blank($warehouseId)) {
            return false;
        }

        return static::authorizedWarehouseQuery()
            ->whereKey($warehouseId)
            ->exists();
    }

    public static function resolveAuthorizedWarehouseId(?string $warehouseId = null): ?string
    {
        if ($warehouseId && static::canAccessWarehouse($warehouseId)) {
            return $warehouseId;
        }

        return static::activeWarehouseId();
    }

    /**
     * @return Collection<int, Warehouse>
     */
    public static function authorizedWarehouses(): Collection
    {
        return static::authorizedWarehouseQuery()
            ->where('is_active', true)
            ->get();
    }

    public static function supplierOptions(): array
    {
        return static::activeCompanyOptions(Supplier::class);
    }

    public static function customerOptions(): array
    {
        return static::activeCompanyOptions(Customer::class);
    }

    public static function productOptions(bool $inventoryOnly = false): array
    {
        $query = static::companyQuery(Product::class)
            ->where('is_active', true);

        if ($inventoryOnly) {
            $query->where('track_inventory', true);
        }

        return $query->pluck('name', 'id')->all();
    }
}
