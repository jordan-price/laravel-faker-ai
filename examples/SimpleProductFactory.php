<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use JordanPrice\LaravelFakerAI\Traits\HasAIContext;

/**
 * A simple product factory using AI context
 */
class ProductFactory extends Factory
{
    use HasAIContext;
    
    /**
     * Fields that should be generated with AI context
     */
    protected array $aiFields = ['name', 'description', 'features'];
    
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // These fields will be generated with AI when using count()
            'name' => fake()->words(3, true),
            'description' => 'A product description',
            'features' => 'Product features list',
            
            // Regular faker fields
            'price' => fake()->randomFloat(2, 10, 1000),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{4}'),
            'in_stock' => fake()->boolean(80),
            'created_at' => fake()->dateTimeThisYear(),
        ];
    }
    
    /**
     * Configure the factory with basic context
     */
    public function configure(): static
    {
        return $this->aiContext([
            'product_type' => 'electronics'
        ]);
    }
    
    /**
     * Make this a tech product
     */
    public function tech(): static
    {
        return $this->aiContext([
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ]);
    }
    
    /**
     * Make this a kitchen product
     */
    public function kitchen(): static
    {
        return $this->aiContext([
            'product_type' => 'kitchenware',
            'industry' => 'home goods'
        ]);
    }
}
