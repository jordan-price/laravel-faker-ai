<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Database\Seeder;

/**
 * Example seeder demonstrating how to use the AI batch generation features
 */
class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example 1: Create a main brand with 3 related sub-brands
        // This uses a single AI call to generate a coherent set of related brands
        Brand::factory()
            ->withRelatedBrands(3)
            ->create([
                'name' => 'GlobalTech',
                'domain' => 'globaltech.com',
            ]);
            
        // Example 2: Create brands across different market segments
        // Each segment will have brands that make sense together
        $segments = ['luxury', 'budget', 'mid-range', 'specialty', 'innovative'];
        
        foreach ($segments as $segment) {
            // Create a primary brand for each segment
            Brand::factory()
                ->marketSegment($segment, 4) // Create 4 related competitor brands in same segment
                ->create([
                    'data' => [
                        'market_segment' => $segment,
                    ],
                ]);
        }
        
        // Example 3: Create a set of brands in a single batch for efficiency
        // First, get a client to associate with these brands
        $client = Client::first() ?? Client::factory()->create();
        
        // Generate 10 technology brands all at once with a single AI call
        // The brands will have relationships and coherence with each other
        $techBrands = fake()->createAIBatch(
            'Brand', 
            10,  // Create 10 brands at once
            [
                'industry' => 'Technology',
                'region' => 'Global',
                'founding_period' => '2010-2020',
            ],
            requiredFields: ['name', 'domain', 'description', 'founding_year', 'focus_area']
        );
        
        // Prepare brands for database insertion with any additional required fields
        $brandsToInsert = collect($techBrands)->map(function ($brand) use ($client) {
            return [
                'client_id' => $client->id,
                'brand_id' => fake()->uuid(),
                'name' => $brand['name'],
                'domain' => $brand['domain'],
                'data' => [
                    'description' => $brand['description'],
                    'founding_year' => $brand['founding_year'],
                    'focus_area' => $brand['focus_area'],
                    'primary_color' => fake()->hexColor(),
                    'secondary_color' => fake()->hexColor(),
                    'font_family' => fake()->randomElement(['Arial', 'Helvetica', 'Roboto', 'Open Sans']),
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();
        
        // Insert all brands at once
        Brand::insert($brandsToInsert);
    }
}
