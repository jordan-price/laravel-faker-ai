<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use JordanPrice\LaravelFakerAI\Traits\HasAIContext;

/**
 * An extremely simple blog post factory with AI context
 */
class BlogPostFactory extends Factory
{
    use HasAIContext;
    
    // Define which fields should use AI context
    protected array $aiFields = ['title', 'content', 'summary'];
    
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence,
            'content' => fake()->paragraphs(3, true),
            'summary' => fake()->sentence,
            'published_at' => fake()->dateTimeThisMonth(),
            'views' => fake()->numberBetween(0, 10000),
            'is_featured' => fake()->boolean(20),
        ];
    }
    
    /**
     * Configure default context
     */
    public function configure(): static
    {
        return $this->aiContext([
            'blog_topic' => 'technology',
        ]);
    }
    
    /**
     * Make these travel-related blog posts
     */
    public function travel(): static
    {
        return $this->aiContext([
            'blog_topic' => 'travel',
            'style' => 'informative travel guides'
        ]);
    }
}
