<?php

namespace Database\Factories;

use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturnItem>
 */
class SaleReturnItemFactory extends Factory
{
    protected $model = SaleReturnItem::class;

    public function configure(): static
    {
        return $this->afterMaking(function (SaleReturnItem $item): void {
            if (! $item->sale_return_id) {
                $saleReturn = SaleReturn::factory()->create();
                $item->sale_return_id = $saleReturn->id;
                $item->company_id = $saleReturn->company_id;
            }

            if (! $item->sale_item_id) {
                $saleItem = SaleItem::factory()->create([
                    'company_id' => $item->company_id,
                ]);
                $item->sale_item_id = $saleItem->id;
                $item->product_id = $saleItem->product_id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_return_id' => null,
            'sale_item_id' => null,
            'company_id' => null,
            'product_id' => null,
            'quantity' => 1,
            'unit_price' => 10,
            'unit_cost' => 5,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'line_total' => 10,
        ];
    }
}
