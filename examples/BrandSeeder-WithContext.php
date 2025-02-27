<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Database\Seeder;

/**
 * Example seeder demonstrating how to use the context-aware factories
 */
class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example 1: Create 5 technology brands that know about each other
        // This uses Laravel's native factory count() method but with AI context
        $techBrands = Brand::factory()
            ->count(5)
            ->create();
            
        // Example 2: Create brands across different market segments
        $segments = ['luxury', 'budget', 'mid-range', 'specialty', 'innovative'];
        
        foreach ($segments as $segment) {
            // Create 3 contextually related brands per segment
            // Each set will be related to each other, but different from other segments
            Brand::factory()
                ->segment($segment)
                ->count(3)
                ->create();
        }
        
        // Example 3: Create brands for different regions, maintaining context
        $regions = ['North America', 'Europe', 'Asia', 'Latin America', 'Middle East'];
        
        foreach ($regions as $region) {
            // Create 3-5 brands per region that make sense together
            $count = fake()->numberBetween(3, 5);
            
            Brand::factory()
                ->region($region)
                ->count($count)
                ->create();
        }
        
        // Example 4: Create sets of competing brands in various industries
        $industries = ['Technology', 'Fashion', 'Automotive', 'Food & Beverage'];
        
        foreach ($industries as $industry) {
            // Create a set of 4 competing brands in this industry
            // These brands will all be contextually aware of each other as competitors
            Brand::factory()
                ->industry($industry)
                ->competitors()
                ->count(4)
                ->create();
        }
        
        // Example 5: Create complementary brands that work well together
        // For example, a family of related products from different categories
        Client::factory()
            ->has(
                Brand::factory()
                    ->complementary()
                    ->count(6)
            )
            ->create([
                'name' => 'Ecosystem Corp',
                'industry' => 'Consumer Electronics'
            ]);
            
        // Example 6: Combining multiple context factors
        Brand::factory()
            ->aiContext([
                'industry' => 'Fitness',
                'target_demographic' => 'athletes',
                'price_point' => 'premium',
                'sustainability' => 'eco-friendly',
                'manufacturing' => 'ethical production',
            ])
            ->count(3)
            ->create();
            
        // Example 7: Disabling AI context for certain cases
        Brand::factory()
            ->withoutAIContext()
            ->count(5)
            ->create();
    }
}
