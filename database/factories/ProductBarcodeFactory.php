<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductBarcode>
 */
class ProductBarcodeFactory extends Factory
{
    protected $model = ProductBarcode::class;

    public function configure(): static
    {
        return $this->afterMaking(function (ProductBarcode $barcode): void {
            if (! $barcode->product_id) {
                $product = Product::factory()->create();
                $barcode->product_id = $product->id;
                $barcode->company_id = $product->company_id;
            }

            if (! $barcode->company_id) {
                $barcode->company_id = Product::query()->findOrFail($barcode->product_id)->company_id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => null,
            'company_id' => null,
            'barcode' => strtoupper(Str::random(13)),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
