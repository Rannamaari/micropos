<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->company(),
            'code' => strtoupper(Str::random(6)),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
