<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    protected $model = PurchaseReturn::class;

    public function configure(): static
    {
        return $this->afterMaking(function (PurchaseReturn $purchaseReturn): void {
            if (! $purchaseReturn->purchase_id) {
                $purchase = Purchase::factory()->create();
                $purchaseReturn->purchase_id = $purchase->id;
                $purchaseReturn->company_id = $purchase->company_id;
                $purchaseReturn->warehouse_id = $purchase->warehouse_id;
                $purchaseReturn->supplier_id = $purchase->supplier_id;
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
            'purchase_id' => null,
            'warehouse_id' => null,
            'supplier_id' => null,
            'purchase_return_number' => strtoupper(fake()->unique()->bothify('PRN-######')),
            'return_date' => now()->toDateString(),
            'subtotal' => 10,
            'tax_total' => 0,
            'grand_total' => 10,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
