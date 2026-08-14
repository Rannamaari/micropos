<?php

namespace Database\Factories;

use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturnItem>
 */
class PurchaseReturnItemFactory extends Factory
{
    protected $model = PurchaseReturnItem::class;

    public function configure(): static
    {
        return $this->afterMaking(function (PurchaseReturnItem $item): void {
            if (! $item->purchase_return_id) {
                $purchaseReturn = PurchaseReturn::factory()->create();
                $item->purchase_return_id = $purchaseReturn->id;
                $item->company_id = $purchaseReturn->company_id;
            }

            if (! $item->purchase_item_id) {
                $purchaseItem = PurchaseItem::factory()->create([
                    'company_id' => $item->company_id,
                ]);
                $item->purchase_item_id = $purchaseItem->id;
                $item->product_id = $purchaseItem->product_id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_return_id' => null,
            'purchase_item_id' => null,
            'company_id' => null,
            'product_id' => null,
            'quantity' => 1,
            'unit_cost' => 10,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'line_total' => 10,
        ];
    }
}
