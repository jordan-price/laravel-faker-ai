<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Minimal blog seeder example
 */
class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 tech blog posts (the default context)
        // They will have related content because of AI context
        BlogPost::factory()
            ->count(5)
            ->create();
            
        // Create 3 travel-related blog posts
        // These will form a coherent series about travel
        BlogPost::factory()
            ->travel()
            ->count(3)
            ->create();
            
        // Create custom topic blog posts
        $topics = ['cooking', 'fitness', 'finance'];
        
        foreach ($topics as $topic) {
            // Create 2 posts per topic that relate to each other
            BlogPost::factory()
                ->aiContext(['blog_topic' => $topic])
                ->count(2)
                ->create();
        }
    }
}
