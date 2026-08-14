<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shortName = Str::lower(Str::random(3));

        return [
            'name' => fake()->unique()->word(),
            'short_name' => $shortName,
            'precision' => fake()->numberBetween(0, 3),
            'is_active' => true,
        ];
    }
}
