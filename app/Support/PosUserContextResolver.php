<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PosUserContextResolver
{
    /**
     * @return array{
     *     company_id: string,
     *     branch_id: string,
     *     warehouse_id: string,
     *     branch: Branch,
     *     warehouse: Warehouse
     * }
     */
    public function resolve(User $user): array
    {
        if (! $user->company_id) {
            throw new HttpException(403, 'POS access requires a company assignment.');
        }

        $warehouse = $this->resolveWarehouse($user);
        $branch = $this->resolveBranch($user, $warehouse);

        if ($warehouse->branch_id !== $branch->id) {
            throw new HttpException(403, 'POS warehouse assignment must belong to the resolved branch.');
        }

        return [
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'branch' => $branch,
            'warehouse' => $warehouse,
        ];
    }

    private function resolveWarehouse(User $user): Warehouse
    {
        if ($user->warehouse_id) {
            $warehouse = Warehouse::query()
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->find($user->warehouse_id);

            if ($warehouse) {
                return $warehouse;
            }
        }

        $query = Warehouse::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $warehouse = $query
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->first();

        if (! $warehouse) {
            throw new HttpException(403, 'POS access requires an active warehouse assignment or a default warehouse.');
        }

        return $warehouse;
    }

    private function resolveBranch(User $user, Warehouse $warehouse): Branch
    {
        if ($user->branch_id) {
            $branch = Branch::query()
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->find($user->branch_id);

            if ($branch) {
                return $branch;
            }
        }

        $branch = Branch::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->find($warehouse->branch_id);

        if (! $branch) {
            throw new HttpException(403, 'POS access requires an active branch assignment or a warehouse-linked branch.');
        }

        return $branch;
    }
}
