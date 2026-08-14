<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class SaleReturnFactory extends Factory
{
    protected $model = SaleReturn::class;

    public function configure(): static
    {
        return $this->afterMaking(function (SaleReturn $saleReturn): void {
            if (! $saleReturn->sale_id) {
                $sale = Sale::factory()->create();
                $saleReturn->sale_id = $sale->id;
                $saleReturn->company_id = $sale->company_id;
                $saleReturn->warehouse_id = $sale->warehouse_id;
                $saleReturn->customer_id = $sale->customer_id;
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
            'warehouse_id' => null,
            'customer_id' => null,
            'sale_return_number' => strtoupper(fake()->unique()->bothify('SRN-######')),
            'return_date' => now()->toDateString(),
            'subtotal' => 10,
            'tax_total' => 0,
            'grand_total' => 10,
            'refund_status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
