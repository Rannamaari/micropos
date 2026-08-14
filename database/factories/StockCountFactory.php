<?php

namespace Database\Factories;

use App\Enums\StockCountStatus;
use App\Models\StockCount;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCount>
 */
class StockCountFactory extends Factory
{
    protected $model = StockCount::class;

    public function configure(): static
    {
        return $this->afterMaking(function (StockCount $count): void {
            if (! $count->warehouse_id) {
                $warehouse = Warehouse::factory()->create();
                $count->warehouse_id = $warehouse->id;
                $count->company_id = $warehouse->company_id;
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
            'warehouse_id' => null,
            'status' => StockCountStatus::InProgress,
            'started_at' => now(),
            'completed_at' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'completed_by' => null,
        ];
    }
}
