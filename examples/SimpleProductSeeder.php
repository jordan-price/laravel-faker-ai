<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Simple product seeder demonstrating AI context usage
 */
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example 1: Basic usage - create 5 related products (same product line)
        Product::factory()
            ->count(5)
            ->create();
            
        // Example 2: Create 3 kitchen products that are related
        Product::factory()
            ->kitchen()
            ->count(3)
            ->create();
            
        // Example 3: Create 4 tech products with custom context
        Product::factory()
            ->tech()
            ->aiContext(['brand' => 'TechBrand'])
            ->count(4)
            ->create();
            
        // Example 4: Create products without AI context (each is random)
        Product::factory()
            ->withoutAIContext()
            ->count(3)
            ->create();
    }
}
