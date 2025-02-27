<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use JordanPrice\LaravelFakerAI\Traits\HasAIContext;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    // Add the HasAIContext trait to enable context-aware AI generation
    use HasAIContext;
    
    /**
     * Define which fields should be generated with AI by default
     * These fields will maintain context between models when creating multiple models
     */
    protected array $aiFields = [
        'name', 
        'domain', 
        'slogan', 
        'description',
        'target_audience',
        'unique_selling_point'
    ];
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // For individual models, use createAIObject directly
        // When creating multiple models with count(), the trait will use createAIBatch instead
        $aiBrand = fake()->createAIObject('Brand', [
            'industry' => 'Technology',
            'market' => 'premium'
        ]);
        
        return [
            'client_id' => Client::factory(),
            'brand_id' => fake()->uuid(),
            
            // AI-generated fields with fallbacks to regular faker
            'name' => $aiBrand['name'] ?? fake()->company(),
            'domain' => $aiBrand['domain'] ?? fake()->domainName(),
            'slogan' => $aiBrand['slogan'] ?? 'Innovating for tomorrow',
            'description' => $aiBrand['description'] ?? 'A technology company focusing on innovation',
            'target_audience' => $aiBrand['target_audience'] ?? 'Business professionals',
            'unique_selling_point' => $aiBrand['unique_selling_point'] ?? 'Advanced technology solutions',
            
            // Regular faker data
            'founded_year' => fake()->year(),
            'is_active' => true,
            
            // Nested data structure
            'data' => [
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
                'font_family' => fake()->randomElement(['Arial', 'Helvetica', 'Roboto', 'Open Sans']),
                // Add any AI-generated nested data here
                'brand_voice' => $aiBrand['brand_voice'] ?? 'Professional',
                'values' => $aiBrand['values'] ?? ['innovation', 'quality', 'reliability'],
            ],
        ];
    }
    
    /**
     * Configure the model factory.
     * Set default context for AI generation
     */
    public function configure(): static
    {
        return $this->aiContext([
            'industry' => 'Technology',
            'market' => 'premium'
        ]);
    }
    
    /**
     * Define brands for a specific industry
     * 
     * @param string $industry
     * @return static
     */
    public function industry(string $industry): static
    {
        return $this->aiContext([
            'industry' => $industry
        ]);
    }
    
    /**
     * Define brands for a market segment
     * 
     * @param string $segment
     * @return static
     */
    public function segment(string $segment): static
    {
        return $this->aiContext([
            'market_segment' => $segment
        ]);
    }
    
    /**
     * Define competing brands
     * 
     * @return static
     */
    public function competitors(): static
    {
        return $this->aiContext([
            'relationship' => 'direct competitors',
            'competition_level' => 'high'
        ]);
    }
    
    /**
     * Define complementary brands that work well together
     * 
     * @return static
     */
    public function complementary(): static
    {
        return $this->aiContext([
            'relationship' => 'complementary brands',
            'partnership_potential' => 'high'
        ]);
    }
    
    /**
     * Define brands for a specific region
     * 
     * @param string $region
     * @return static
     */
    public function region(string $region): static
    {
        return $this->aiContext([
            'region' => $region,
            'market_focus' => $region . ' markets'
        ]);
    }
}
