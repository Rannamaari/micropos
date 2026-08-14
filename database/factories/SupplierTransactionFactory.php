<?php

namespace Database\Factories;

use App\Enums\SupplierTransactionType;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierTransaction>
 */
class SupplierTransactionFactory extends Factory
{
    protected $model = SupplierTransaction::class;

    public function configure(): static
    {
        return $this->afterMaking(function (SupplierTransaction $transaction): void {
            if (! $transaction->supplier_id) {
                $supplier = Supplier::factory()->create();
                $transaction->supplier_id = $supplier->id;
                $transaction->company_id = $supplier->company_id;
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
            'supplier_id' => null,
            'type' => SupplierTransactionType::Purchase,
            'amount' => fake()->randomFloat(4, 10, 1000),
            'reference_type' => null,
            'reference_id' => null,
            'reference_number' => null,
            'description' => fake()->optional()->sentence(),
            'occurred_at' => now(),
            'created_by' => null,
        ];
    }
}
