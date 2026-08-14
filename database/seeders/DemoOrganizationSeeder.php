<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoOrganizationSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['name' => 'Micro POS Demo Company'],
            [
                'legal_name' => 'Micro POS Demo Company',
                'timezone' => 'Indian/Maldives',
                'currency' => 'MVR',
                'is_active' => true,
            ]
        );

        $branch = Branch::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'city' => 'Male',
                'is_active' => true,
            ]
        );

        Warehouse::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'MAIN-WH'],
            [
                'branch_id' => $branch->id,
                'name' => 'Main Warehouse',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@micropos.local'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'warehouse_id' => null,
                'name' => 'Micro POS Admin',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $user->syncRoles(['super-admin']);
    }
}
