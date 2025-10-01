<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Article Factory
 *
 * Factory for creating Article model instances in tests and seeding
 */
class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = $this->faker->unique()->url();

        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'url' => $url,
            'url_sha1' => sha1($url),
            'image_url' => $this->faker->optional(0.7)->imageUrl(800, 600, 'business'),
            'author' => $this->faker->optional(0.8)->name(),
            'source_id' => Source::factory(),
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'provider' => $this->faker->randomElement(['newsapi', 'guardian', 'nyt']),
            'category' => $this->faker->randomElement(['general', 'business', 'technology', 'sports', 'health', 'science', 'entertainment']),
        ];
    }

    /**
     * Indicate that the article is from a specific source.
     */
    public function fromSource(Source $source): static
    {
        return $this->state(fn (array $attributes) => [
            'source_id' => $source->id,
        ]);
    }

    /**
     * Indicate that the article is in a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Indicate that the article is from a specific provider.
     */
    public function provider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }

    /**
     * Indicate that the article was published recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the article was published on a specific date.
     */
    public function publishedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $date,
        ]);
    }

    /**
     * Indicate that the article has a specific title.
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Indicate that the article has a specific author.
     */
    public function byAuthor(string $author): static
    {
        return $this->state(fn (array $attributes) => [
            'author' => $author,
        ]);
    }
}
