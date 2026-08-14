<?php

namespace Database\Factories;

use App\Enums\CustomerTransactionType;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerTransaction>
 */
class CustomerTransactionFactory extends Factory
{
    protected $model = CustomerTransaction::class;

    public function configure(): static
    {
        return $this->afterMaking(function (CustomerTransaction $transaction): void {
            if (! $transaction->customer_id) {
                $customer = Customer::factory()->create();
                $transaction->customer_id = $customer->id;
                $transaction->company_id = $customer->company_id;
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
            'customer_id' => null,
            'type' => CustomerTransactionType::Sale,
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
