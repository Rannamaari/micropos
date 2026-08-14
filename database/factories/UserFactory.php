<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'branch_id' => null,
            'warehouse_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(fn () => [
            'company_id' => ($company ?? Company::factory()->create())->id,
            'branch_id' => null,
            'warehouse_id' => null,
        ]);
    }

    public function forBranch(?Branch $branch = null): static
    {
        $branch ??= Branch::factory()->create();

        return $this->state(fn () => [
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'warehouse_id' => null,
        ]);
    }

    public function forWarehouse(?Warehouse $warehouse = null): static
    {
        $warehouse ??= Warehouse::factory()->create();

        return $this->state(fn () => [
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
        ]);
    }
}
