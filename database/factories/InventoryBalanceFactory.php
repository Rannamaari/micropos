<?php

namespace Database\Factories;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    protected $model = InventoryBalance::class;

    public function configure(): static
    {
        return $this->afterMaking(function (InventoryBalance $balance): void {
            if (! $balance->warehouse_id) {
                $warehouse = Warehouse::factory()->create();
                $balance->warehouse_id = $warehouse->id;
                $balance->company_id = $warehouse->company_id;
            }

            if (! $balance->product_id) {
                $balance->product_id = Product::factory()->create([
                    'company_id' => $balance->company_id,
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
            'quantity' => fake()->randomFloat(4, 0, 100),
        ];
    }
}
