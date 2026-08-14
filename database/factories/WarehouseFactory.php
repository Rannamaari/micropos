<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Warehouse $warehouse): void {
            if (! $warehouse->company_id && $warehouse->branch_id) {
                $warehouse->company_id = Branch::query()->findOrFail($warehouse->branch_id)->company_id;
            }

            if (! $warehouse->company_id) {
                $warehouse->company_id = Company::factory()->create()->id;
            }

            if (! $warehouse->branch_id) {
                $warehouse->branch_id = Branch::factory()->create([
                    'company_id' => $warehouse->company_id,
                ])->id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'branch_id' => null,
            'name' => fake()->company().' Warehouse',
            'code' => strtoupper(Str::random(6)),
            'address' => fake()->optional()->address(),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
