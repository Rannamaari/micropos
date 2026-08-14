<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerPayment>
 */
class CustomerPaymentFactory extends Factory
{
    protected $model = CustomerPayment::class;

    public function configure(): static
    {
        return $this->afterMaking(function (CustomerPayment $payment): void {
            if (! $payment->customer_id) {
                $customer = Customer::factory()->create();
                $payment->customer_id = $customer->id;
                $payment->company_id = $customer->company_id;
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
            'sale_id' => null,
            'payment_method' => 'cash',
            'amount' => 20,
            'reference' => fake()->optional()->bothify('PAY-####'),
            'notes' => fake()->optional()->sentence(),
            'paid_at' => now(),
            'created_by' => null,
        ];
    }
}
