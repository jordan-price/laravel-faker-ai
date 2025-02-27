<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Example of a Laravel factory that uses AI batch generation
 * to create coherent collections of related brands
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Basic approach: use standard Faker data
        return [
            'client_id' => Client::factory(),
            'brand_id' => fake()->uuid(),
            'name' => fake()->company(),
            'domain' => fake()->domainName(),
            'data' => [
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
                'font_family' => fake()->randomElement(['Arial', 'Helvetica', 'Roboto', 'Open Sans']),
            ],
        ];
    }

    /**
     * Variant using AI to enhance individual brands with richer, more realistic data
     */
    public function aiEnhanced()
    {
        return $this->state(function (array $attributes) {
            // Get the basic AI object for a brand
            $brandData = fake()->createAIObject('Brand', [
                'name' => $attributes['name'],
                'domain' => $attributes['domain'],
            ]);
            
            // Keep the existing fields but enhance with AI data
            return [
                'data' => array_merge($attributes['data'], [
                    'description' => $brandData['description'] ?? 'A modern brand',
                    'slogan' => $brandData['slogan'] ?? 'Creating a better future',
                    'target_audience' => $brandData['target_audience'] ?? 'Professionals',
                    'brand_voice' => $brandData['brand_voice'] ?? 'Professional yet friendly',
                ]),
            ];
        });
    }

    /**
     * Create a brand along with multiple coherent sub-brands at once
     * 
     * @param int $count Number of related brands to create
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withRelatedBrands(int $count = 3)
    {
        return $this->afterCreating(function (Brand $brand) use ($count) {
            // Get the client to assign all related brands to
            $client = Client::find($brand->client_id);
            
            // Generate a batch of related brands in a single API call
            // This ensures the brands have a coherent relationship with each other
            $relatedBrandsData = fake()->createAIBatch(
                'Brand', 
                $count,
                [
                    'parent_brand' => $brand->name,
                    'parent_industry' => $client->industry ?? 'Technology',
                    'relationship' => 'sub-brand' // Gives context for the AI to generate appropriate related brands
                ]
            );
            
            // Add required database fields to each generated brand
            $brandsToInsert = collect($relatedBrandsData)->map(function ($brandData) use ($client) {
                return [
                    'client_id' => $client->id,
                    'brand_id' => fake()->uuid(),
                    'name' => $brandData['name'],
                    'domain' => $brandData['domain'] ?? fake()->domainName(),
                    'data' => [
                        'primary_color' => fake()->hexColor(),
                        'secondary_color' => fake()->hexColor(),
                        'font_family' => fake()->randomElement(['Arial', 'Helvetica', 'Roboto', 'Open Sans']),
                        'description' => $brandData['description'] ?? null,
                        'slogan' => $brandData['slogan'] ?? null,
                        'target_audience' => $brandData['target_audience'] ?? null,
                        'related_to' => $client->name,
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            
            // Insert all related brands at once
            Brand::insert($brandsToInsert);
        });
    }

    /**
     * Create a collection of related brands for a specific market segment
     * 
     * @param string $segment Market segment (e.g., "luxury", "budget", "specialty")
     * @param int $count Number of brands to create
     * @return \Illuminate\Database\Eloquent\Factories\Factory 
     */
    public function marketSegment(string $segment, int $count = 5)
    {
        return $this->afterCreating(function (Brand $mainBrand) use ($segment, $count) {
            $client = Client::find($mainBrand->client_id);
            
            // Use createAIBatch to generate a coherent set of brands
            // that all fit within the specified market segment
            $competitorBrands = fake()->createAIBatch(
                'Brand',
                $count,
                [
                    'industry' => $client->industry ?? 'Retail',
                    'market_segment' => $segment,
                    'competition_level' => 'direct competitors',
                ],
                requiredFields: ['name', 'domain', 'description', 'unique_selling_point']
            );
            
            // Process and insert the brands
            $brandsToInsert = collect($competitorBrands)->map(function ($brandData) use ($client, $segment) {
                return [
                    'client_id' => $client->id,
                    'brand_id' => fake()->uuid(),
                    'name' => $brandData['name'],
                    'domain' => $brandData['domain'],
                    'data' => [
                        'primary_color' => fake()->hexColor(),
                        'secondary_color' => fake()->hexColor(),
                        'font_family' => fake()->randomElement(['Arial', 'Helvetica', 'Roboto', 'Open Sans']),
                        'description' => $brandData['description'],
                        'market_segment' => $segment,
                        'unique_selling_point' => $brandData['unique_selling_point'],
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            
            Brand::insert($brandsToInsert);
        });
    }
}
