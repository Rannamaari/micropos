<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCountItem>
 */
class StockCountItemFactory extends Factory
{
    protected $model = StockCountItem::class;

    public function configure(): static
    {
        return $this->afterMaking(function (StockCountItem $item): void {
            if (! $item->stock_count_id) {
                $count = StockCount::factory()->create();
                $item->stock_count_id = $count->id;
                $item->company_id = $count->company_id;
                $item->warehouse_id = $count->warehouse_id;
            }

            if (! $item->product_id) {
                $item->product_id = Product::factory()->create([
                    'company_id' => $item->company_id,
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
            'stock_count_id' => null,
            'company_id' => null,
            'warehouse_id' => null,
            'product_id' => null,
            'system_quantity' => fake()->randomFloat(4, 0, 100),
            'counted_quantity' => null,
            'difference' => null,
            'unit_cost' => fake()->randomFloat(4, 0, 100),
        ];
    }
}
