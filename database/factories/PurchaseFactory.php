<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Purchase $purchase): void {
            if (! $purchase->warehouse_id) {
                $warehouse = Warehouse::factory()->create();
                $purchase->warehouse_id = $warehouse->id;
                $purchase->company_id = $warehouse->company_id;
                $purchase->branch_id = $warehouse->branch_id;
            }

            if (! $purchase->supplier_id) {
                $supplier = Supplier::factory()->create([
                    'company_id' => $purchase->company_id,
                ]);
                $purchase->supplier_id = $supplier->id;
            }

            if (! $purchase->branch_id && $purchase->company_id) {
                $purchase->branch_id = Branch::query()->where('company_id', $purchase->company_id)->value('id');
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
            'warehouse_id' => null,
            'supplier_id' => null,
            'purchase_number' => strtoupper(fake()->unique()->bothify('PUR-######')),
            'supplier_invoice_number' => fake()->optional()->bothify('INV-####'),
            'status' => PurchaseStatus::Ordered,
            'purchase_date' => now()->toDateString(),
            'expected_date' => null,
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'other_cost_total' => 0,
            'grand_total' => 100,
            'paid_total' => 0,
            'balance_due' => 100,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'received_by' => null,
            'received_at' => null,
        ];
    }
}
