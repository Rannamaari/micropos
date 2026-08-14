<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => strtoupper(fake()->unique()->bothify('SUP-####')),
            'name' => fake()->company(),
            'legal_name' => fake()->optional()->company(),
            'contact_person' => fake()->optional()->name(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'registration_number' => fake()->optional()->bothify('REG-####'),
            'tax_number' => fake()->optional()->bothify('TAX-####'),
            'address' => fake()->optional()->address(),
            'city' => fake()->optional()->city(),
            'country' => fake()->optional()->country(),
            'credit_limit' => fake()->optional()->randomFloat(4, 1000, 10000),
            'payment_terms_days' => fake()->optional()->randomElement([7, 14, 30]),
            'opening_balance' => 0,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
