<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function configure(): static
    {
        return $this->afterMaking(function (StockMovement $movement): void {
            if (! $movement->warehouse_id) {
                $warehouse = Warehouse::factory()->create();
                $movement->warehouse_id = $warehouse->id;
                $movement->company_id = $warehouse->company_id;
            }

            if (! $movement->product_id) {
                $movement->product_id = Product::factory()->create([
                    'company_id' => $movement->company_id,
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
            'warehouse_id' => null,
            'product_id' => null,
            'type' => StockMovementType::AdjustmentIn,
            'quantity' => 5,
            'quantity_before' => 10,
            'quantity_after' => 15,
            'unit_cost' => fake()->randomFloat(4, 0, 100),
            'reference_type' => null,
            'reference_id' => null,
            'reference_number' => null,
            'reason' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'occurred_at' => now(),
        ];
    }
}
