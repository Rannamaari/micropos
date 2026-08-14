<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(Str::random(6)),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'is_active' => true,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn () => [
            'company_id' => $parent->company_id,
            'parent_id' => $parent->id,
        ]);
    }
}
