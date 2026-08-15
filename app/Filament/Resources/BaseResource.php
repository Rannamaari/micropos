<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    protected static ?string $companyScopeColumn = 'company_id';

    protected static ?string $viewPermission = null;

    protected static ?string $createPermission = null;

    protected static ?string $updatePermission = null;

    protected static ?string $deletePermission = null;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = static::getUser();

        if (! $user || $user->hasRole('super-admin') || ! static::$companyScopeColumn || ! $user->company_id) {
            return $query;
        }

        return $query->where(static::$companyScopeColumn, $user->company_id);
    }

    public static function canViewAny(): bool
    {
        return static::hasPermission(static::$viewPermission);
    }

    public static function canCreate(): bool
    {
        return static::hasPermission(static::$createPermission ?? static::$updatePermission ?? static::$viewPermission);
    }

    public static function canEdit(Model $record): bool
    {
        return static::recordBelongsToUserCompany($record) && static::hasPermission(static::$updatePermission ?? static::$createPermission ?? static::$viewPermission);
    }

    public static function canDelete(Model $record): bool
    {
        return static::recordBelongsToUserCompany($record) && static::hasPermission(static::$deletePermission ?? static::$updatePermission ?? static::$createPermission);
    }

    public static function canDeleteAny(): bool
    {
        return static::hasPermission(static::$deletePermission ?? static::$updatePermission ?? static::$createPermission);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    protected static function getUser(): ?User
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected static function companyId(): ?string
    {
        return static::getUser()?->company_id;
    }

    protected static function hasPermission(?string $permission): bool
    {
        if (! $permission) {
            return true;
        }

        $user = static::getUser();

        return (bool) $user?->can($permission);
    }

    protected static function recordBelongsToUserCompany(Model $record): bool
    {
        $user = static::getUser();

        if (! $user || $user->hasRole('super-admin') || ! static::$companyScopeColumn) {
            return true;
        }

        $column = static::$companyScopeColumn;

        return (string) ($record->getAttribute($column) ?? '') === (string) $user->company_id;
    }
}
