<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalePayment>
 */
class SalePaymentFactory extends Factory
{
    protected $model = SalePayment::class;

    public function configure(): static
    {
        return $this->afterMaking(function (SalePayment $payment): void {
            if (! $payment->sale_id) {
                $sale = Sale::factory()->create();
                $payment->sale_id = $sale->id;
                $payment->company_id = $sale->company_id;
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
            'sale_id' => null,
            'payment_method' => 'cash',
            'amount' => 20,
            'amount_tendered' => 20,
            'change_due' => 0,
            'reference' => fake()->optional()->bothify('PAY-####'),
            'notes' => fake()->optional()->sentence(),
            'paid_at' => now(),
            'created_by' => null,
        ];
    }
}
