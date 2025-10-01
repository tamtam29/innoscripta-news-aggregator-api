<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Source Factory
 *
 * Factory for creating Source model instances in tests and seeding
 */
class SourceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Source::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => $this->faker->unique()->slug(),
            'name' => $this->faker->company() . ' News',
            'url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['general', 'business', 'technology', 'sports', 'health', 'science']),
            'country' => $this->faker->countryCode(),
            'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
            'provider' => $this->faker->randomElement(['newsapi', 'guardian', 'nyt']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the source is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the source is for a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Indicate that the source is for a specific country.
     */
    public function country(string $country): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => $country,
        ]);
    }
}
