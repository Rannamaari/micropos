<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePayment>
 */
class PurchasePaymentFactory extends Factory
{
    protected $model = PurchasePayment::class;

    public function configure(): static
    {
        return $this->afterMaking(function (PurchasePayment $payment): void {
            if (! $payment->purchase_id) {
                $purchase = Purchase::factory()->create();
                $payment->purchase_id = $purchase->id;
                $payment->company_id = $purchase->company_id;
                $payment->supplier_id = $purchase->supplier_id;
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
            'supplier_id' => null,
            'payment_method' => 'cash',
            'amount' => 25,
            'reference' => fake()->optional()->bothify('PAY-####'),
            'notes' => fake()->optional()->sentence(),
            'paid_at' => now(),
            'created_by' => null,
        ];
    }
}
