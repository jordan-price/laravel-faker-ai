# Laravel Faker AI

A Laravel package that extends FakerPHP by adding an AI-powered data generator using Prism, allowing you to generate more realistic and context-aware fake data in your Laravel applications. This package supports multiple AI providers including Ollama, OpenAI, Anthropic, and Mistral.

## Installation

Install the package via Composer:

```bash
composer require jordan-price/laravel-faker-ai
```

The package will automatically register its service provider if you're using Laravel's package auto-discovery.

## Configuration

Next, execute the install command:

```bash
php artisan faker-ai:install
```

This will create a `config/faker-ai.php` configuration file in your project, which you can modify to your needs using environment variables.

Make sure you have your AI configuration set in your `.env` file:

```
# Choose your AI provider: ollama, openai, anthropic, or mistral
FAKER_AI_PROVIDER=ollama

# Model configuration for each provider
FAKER_OLLAMA_MODEL=llama3
FAKER_OPENAI_MODEL=gpt-3.5-turbo
FAKER_ANTHROPIC_MODEL=claude-3-sonnet-20240229
FAKER_MISTRAL_MODEL=mistral-tiny

# Caching configuration
FAKER_AI_ENABLE_CACHE=true
FAKER_AI_CACHE_TTL=1440
```

### Setting up Ollama Integration

This package provides built-in support for [Ollama](https://ollama.ai), allowing you to run AI models locally without relying on external APIs.

#### Prerequisites for Ollama

1. **Install Ollama**: Download and install from [ollama.ai](https://ollama.ai)
2. **Start the Ollama service**: Ensure it's running at `http://localhost:11434`
3. **Download a model**: Using the Ollama CLI, download a suitable model:
   ```bash
   ollama pull llama3
   ```

#### Required dependencies

Make sure you have the Prism package installed:

```bash
composer require echolabsdev/prism
```

#### Ollama Configuration

Set these environment variables in your `.env` file:

```
FAKER_AI_PROVIDER=ollama
FAKER_OLLAMA_MODEL=llama3
FAKER_AI_ENABLE_CACHE=true
FAKER_AI_CACHE_TTL=1440
```

You can check available models on your Ollama instance with:

```bash
ollama list
```

#### Testing the Ollama Integration

To verify that Ollama is working correctly with your application:

```php
// Create a simple test
$productName = fake()->promptAI('productName', context: [
    'product_type' => 'technology',
    'industry' => 'consumer electronics'
]);

echo "Generated product name: " . $productName;
```

#### Troubleshooting Ollama

If you encounter issues:

1. **Verify Ollama is running**: 
   ```bash
   curl http://localhost:11434/api/tags
   ```

2. **Check model availability**:
   ```bash
   ollama list
   ```

3. **Test direct API access**:
   ```bash
   curl -X POST http://localhost:11434/api/generate -d '{
     "model": "llama3",
     "prompt": "Generate a name for a smartphone"
   }'
   ```

4. **Enable debug logging** in your Laravel application:
   ```
   LOG_LEVEL=debug
   ```

## Usage

The package adds a new `promptAI()` method to the Faker generator. You can use it in several ways:

### Basic Usage

```php
$faker = app(\Faker\Generator::class);

// Generate a fake name using AI
$name = $faker->promptAI('name');

// Generate a movie review
$review = $faker->promptAI('movieReview');

// Generate a movie description
$description = $faker->promptAI('movieDescription');
```

You can also use the built-in `fake()` helper:

```php
$name = fake()->promptAI('name');
```

### Using Specific Providers

You can specify which AI provider and model to use:

```php
// Use OpenAI's GPT-4 model
$name = $faker->promptAI('name', provider: 'openai', model: 'gpt-4');

// Use Anthropic's Claude model
$description = $faker->promptAI('productDescription', provider: 'anthropic', model: 'claude-3-sonnet-20240229');

// Use Ollama's Llama model
$review = $faker->promptAI('movieReview', provider: 'ollama', model: 'llama3');
```

### Context-Aware Data Generation

One of the most powerful features of Laravel Faker AI is its ability to generate context-aware data. You can pass a context array to the `promptAI()` method to get data that's related to other properties:

```php
// Generate a product description based on its name and category
$productName = 'Ergonomic Office Chair';
$productCategory = 'Office Furniture';

$description = $faker->promptAI(
    'productDescription',
    context: [
        'name' => $productName,
        'category' => $productCategory,
        'price' => '$199.99'
    ]
);

// Generate a blog post content based on its title and tags
$blogTitle = '10 Ways to Improve Your Productivity';
$blogTags = ['productivity', 'work-life balance', 'time management'];

$content = $faker->promptAI(
    'blogContent',
    context: [
        'title' => $blogTitle,
        'tags' => $blogTags,
        'audience' => 'professionals'
    ]
);
```

This is particularly useful in database seeders to create more realistic related content:

```php
Product::factory()->create()->each(function ($product) use ($faker) {
    // Generate reviews that are specifically about this product
    Review::factory()->count(3)->create([
        'product_id' => $product->id,
        'content' => $faker->promptAI('productReview', context: [
            'product_name' => $product->name,
            'product_category' => $product->category,
            'rating' => rand(3, 5) // Generate mostly positive reviews
        ])
    ]);
});
```

### Generating Complete Objects at Once

Rather than generating each field individually, you can use the `promptAIObject()` method to generate multiple fields for an object in a single API call:

```php
// Generate multiple product fields at once
$productFields = fake()->promptAIObject(
    // List of fields to generate
    ['description', 'features', 'metaDescription', 'marketingSlogan'],
    // Context for all fields
    [
        'name' => 'Ultra HD Smart TV',
        'category' => 'Electronics',
        'price' => '$899.99'
    ]
);

// Create the product with all generated fields
$product = Product::create([
    'name' => 'Ultra HD Smart TV',
    'price' => 899.99,
    'category_id' => $category->id,
    'description' => $productFields['description'],
    'features' => $productFields['features'],
    'meta_description' => $productFields['metaDescription'],
    'slogan' => $productFields['marketingSlogan']
]);
```

This approach has several advantages:
- Makes a single API call instead of multiple calls
- Ensures consistency across all generated fields
- More efficient and faster for generating multiple related fields
- All fields have access to the same context information

You can also use this with Laravel's factories:

```php
// Define a factory state that uses promptAIObject
public function withAIContent()
{
    return $this->state(function (array $attributes) {
        $name = $attributes['name'] ?? ucwords($this->faker->words(rand(2, 3), true));
        $category = Category::find($attributes['category_id'] ?? 1)->name ?? 'General';
        
        // Generate all content fields at once
        $aiContent = $this->faker->promptAIObject(
            ['description', 'features', 'metaDescription', 'marketingPoints'], 
            [
                'name' => $name,
                'category' => $category,
                'price' => '$' . number_format($attributes['price'] ?? 99.99, 2)
            ]
        );
        
        // Merge the generated fields into the attributes
        return $aiContent;
    });
}
```

Usage in a seeder:

```php
// Create a main brand and 5 contextually related sub-brands in a single step
Brand::factory()->createRelatedBrands(5)->create();
```

This creates a collection of brands that have a coherent theme and relationship with each other, rather than just random individual brands.

### Creating Complete Objects from Scratch with AI

For the ultimate simplicity, Laravel Faker AI can create **entire objects from scratch** with the `createAIObject()` method:

```php
// Create a complete product with just a category
$product = fake()->createAIObject('Product', [
    'category' => 'Smartphones'
]);

// The AI will generate a complete product with all necessary fields
// $product = [
//     'name' => 'UltraPhone X15',
//     'description' => 'The UltraPhone X15 is a flagship smartphone...',
//     'price' => 899.99,
//     'features' => 'Triple camera, 5G connectivity, 6.5" display...',
//     'colors' => ['Black', 'Silver', 'Gold'],
//     'specifications' => ['CPU' => 'Octa-core', 'RAM' => '8GB'...],
//     ...and many more fields
// ]

// You can create the database record directly
Product::create($product);
```

This approach:
- Requires minimal input to generate complete, realistic objects
- AI determines appropriate fields based on the object type
- Creates consistent, related properties that make sense together
- Perfect for rapid prototyping and demo data generation

You can ensure specific fields are included:

```php
// Require specific fields to be present
$blogPost = fake()->createAIObject(
    'BlogPost',
    ['topic' => 'Artificial Intelligence', 'audience' => 'beginners'],
    requiredFields: ['title', 'content', 'tags', 'readingTime', 'difficulty']
);

Post::create($blogPost);
```

And it integrates perfectly with Laravel factories:

```php
// Define a factory that generates entire objects at once
public function definition()
{
    $category = Category::inRandomOrder()->first();
    
    // Let AI generate the entire product based on minimal info
    return $this->faker->createAIObject('Product', [
        'category' => $category->name,
        'price_range' => $this->faker->randomElement(['budget', 'mid-range', 'premium'])
    ]);
}
```

For even more simplicity in seeders:

```php
// Generate 100 realistic products with just two lines of code
public function run()
{
    foreach (Category::all() as $category) {
        // Create 20 products per category, AI generates everything
        $products = collect(range(1, 20))->map(function() use ($category) {
            return fake()->createAIObject('Product', [
                'category' => $category->name
            ]);
        });
        
        // Bulk insert all products at once
        Product::insert($products->toArray());
    }
}
```

### Generating Multiple Related Objects in a Batch

To generate multiple related objects that maintain context with each other, use the `createAIBatch()` method:

```php
// Generate 5 related technology brands in a single API call
$brands = fake()->createAIBatch(
    'Brand',             // Type of object
    5,                   // Number to create
    [
        'industry' => 'Technology',
        'market' => 'consumer electronics'
    ]
);

// Each brand in the array will be contextually related to the others
// For example, they might all be smartphone manufacturers or all be software companies
// but each will be unique and make sense together as a collection

// You can then save all brands at once
Brand::insert($brands);
```

This approach is ideal for creating related sets of data, such as:
- A collection of products in the same category
- A set of related blog posts
- Multiple users from the same department
- A series of related events

Example with a Laravel factory:

```php
<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create regular faker data
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
    
    // Add context seed information
    public function configure(): static
    {
        return $this->aiContext([
            'product_type' => 'electronics'
        ]);
    }
    
    // Custom context methods
    public function tech(): static
    {
        return $this->aiContext([
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ]);
    }
}
```

### Comparing AI Provider Options

| Provider  | Advantages                     | Considerations                               | Use Case                         |
|-----------|--------------------------------|----------------------------------------------|----------------------------------|
| Ollama    | - Free to use                  | - Requires local installation                | - Development & testing          |
|           | - Private, no data sharing     | - Limited by local compute resources         | - Privacy-sensitive applications  |
|           | - No API keys needed           | - Model quality may vary                     | - Offline development            |
|           | - Works offline                |                                              |                                  |
| OpenAI    | - High quality results         | - Requires API key                           | - Production applications        |
|           | - Best model availability      | - Cost based on usage                        | - When quality is critical       |
|           | - Fast response times          | - Data sent to external servers              |                                  |
| Anthropic | - Excellent context handling   | - Requires API key                           | - Complex data generation        |
|           | - Strong in complex reasoning  | - Higher cost than some alternatives         | - Nuanced content                |
|           | - Long context windows         | - Data sent to external servers              |                                  |
| Mistral   | - Good balance of quality/cost | - Requires API key                           | - Budget-conscious production    |
|           | - European-based alternative   | - Smaller model selection than OpenAI        | - EU data compliance needs       |
|           | - Strong multilingual support  | - Data sent to external servers              |                                  |

### Simple Product Factory

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use JordanPrice\LaravelFakerAI\Traits\HasAIContext;

class ProductFactory extends Factory
{
    use HasAIContext;
    
    // Define fields that should be AI-generated with context
    protected array $aiFields = ['name', 'description', 'features'];
    
    public function definition(): array
    {
        return [
            // Fields that will be AI-generated with context
            'name' => fake()->words(3, true),
            'description' => 'A product description',
            'features' => 'Product features list',
            
            // Regular faker fields
            'price' => fake()->randomFloat(2, 10, 1000),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{4}'),
            'in_stock' => fake()->boolean(80),
        ];
    }
    
    // Add context seed information
    public function configure(): static
    {
        return $this->aiContext([
            'product_type' => 'electronics'
        ]);
    }
    
    // Custom context methods
    public function tech(): static
    {
        return $this->aiContext([
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ]);
    }
}
```

#### Simple Blog Post Factory

```php
<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use JordanPrice\LaravelFakerAI\Traits\HasAIContext;

class BlogPostFactory extends Factory
{
    use HasAIContext;
    
    // Define AI context fields
    protected array $aiFields = ['title', 'content', 'summary'];
    
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
    
    public function travel(): static
    {
        return $this->aiContext([
            'blog_topic' => 'travel',
            'style' => 'informative travel guides'
        ]);
    }
}
```

### Using the Context-Aware Factories

With minimal implementation, you can create sets of related models:

```php
// Create 5 tech products that are contextually related
$products = Product::factory()
    ->tech()
    ->count(5)
    ->create();

// Create 3 travel blog posts that form a coherent series
$travelBlogs = BlogPost::factory()
    ->travel()
    ->count(3)
    ->create();
    
// Create blog posts for different topics
$topics = ['cooking', 'fitness', 'finance'];

foreach ($topics as $topic) {
    // Create 2 related posts per topic
    BlogPost::factory()
        ->aiContext(['blog_topic' => $topic])
        ->count(2)
        ->create();
}

// Disable AI context if needed
$randomProducts = Product::factory()
    ->withoutAIContext()
    ->count(3)
    ->create();
```

### Simple Seeder Implementation

Here's a simple seeder example:

```php
<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 related products (same product line)
        Product::factory()
            ->count(5)
            ->create();
            
        // Create 3 kitchen products that are related
        Product::factory()
            ->aiContext([
                'product_type' => 'kitchenware',
                'industry' => 'home goods'
            ])
            ->count(3)
            ->create();
    }
}
```

## How It Works

When you call `count()` on a factory with the `HasAIContext` trait:

1. The trait intercepts the creation process
2. Instead of making separate API calls for each model, it makes a single batch call
3. All generated models share contextual awareness with each other
4. The fields listed in `$aiFields` property are generated with this shared context

This means your generated data is more coherent and realistic. For example:

- A set of related blog posts will all be about the same topic
- A family of products will have consistent naming conventions and features
- A group of brands will be appropriately positioned relative to each other

The trait is designed to work seamlessly with Laravel's existing factory system - you just add the trait and define which fields should maintain context.

### Core Functionality

1. **Define Context-Aware Fields**:
   ```php
   protected array $aiFields = ['name', 'description', 'features'];
   ```

2. **Add Context Information**:
   ```php
   // In a method
   public function luxury(): static
   {
       return $this->aiContext([
           'market_segment' => 'luxury',
           'price_point' => 'premium'
       ]);
   }
   
   // Or directly in a chain
   Product::factory()
       ->aiContext(['industry' => 'technology'])
       ->count(3)
       ->create();
   ```

3. **Enable/Disable as Needed**:
   ```php
   // Disable AI context
   $randomModels = Product::factory()
       ->withoutAIContext()
       ->count(5)
       ->create();
       
   // Re-enable it
   $relatedModels = Product::factory()
       ->withAIContext()
       ->count(3)
       ->create();
   ```

### With Fallback Values

You can provide fallback values that will be used if the AI request fails:

```php
// String fallback
$name = $faker->promptAI('name', fallback: 'John Doe');

// Closure fallback
$name = $faker->promptAI('name', fallback: fn() => 'John Doe');
```

### Error Handling

By default, errors are logged and the fallback value is returned. You can make it throw exceptions instead:

```php
try {
    $name = $faker->promptAI('name', throwOnError: true);
} catch (\Exception $e) {
    // Handle the exception
    Log::error("AI Faker failed: {$e->getMessage()}");
}
```

## Example Use Cases

Laravel Faker AI is perfect for generating various types of fake data:

- User profiles with realistic names, bios, and interests
- Product descriptions for e-commerce applications
- Movie or book reviews with varying sentiments
- Social media posts and comments
- Realistic addresses and geographical information
- Technical documentation and code comments
- Job descriptions and career histories

## Caching

To improve performance and reduce API calls, Laravel Faker AI includes built-in caching. 
For identical prompt requests, the package will return cached responses based on your configuration.
You can adjust cache settings in your config file:

```php
// config/faker-ai.php
return [
    // ...
    'enable_cache' => env('FAKER_AI_ENABLE_CACHE', true),
    'cache_ttl' => env('FAKER_AI_CACHE_TTL', 1440), // minutes
    // ...
];
```

### Performance Considerations with Ollama

When using Ollama for local AI generation, keep these performance tips in mind:

1. **Enable Caching**: Always enable caching to avoid repeatedly generating the same content.
   ```
   FAKER_AI_ENABLE_CACHE=true
   FAKER_AI_CACHE_TTL=1440  # 24 hours in minutes
   ```

2. **Choose the Right Model Size**: Smaller models will be significantly faster:
   - For simple text generation, try `llama3:7b` instead of larger variants
   - For development, smaller models are usually sufficient
   - For production quality, larger models may be worth the performance cost

3. **Batch Generation**: When seeding databases, use the factory's batch methods rather than creating models one by one.
   ```php
   // More efficient - generates all models in batches
   Product::factory()->count(50)->create();
   
   // Less efficient - generates each model separately
   for ($i = 0; $i < 50; $i++) {
       Product::factory()->create();
   }
   ```

4. **Hardware Considerations**: Ollama's performance depends on your local hardware:
   - CPU-only generation will be significantly slower than with a GPU
   - Consider using cloud providers for large-scale generation if you don't have a GPU

5. **Parallel Processing**: For large datasets, consider using Laravel's queue system with multiple workers to parallelize generation.

## Testing

The package includes a test suite that you can run with:

```bash
composer test
```

For testing in your own application, you may want to mock the AI responses:

```php
// In your test
$this->mock(FakerAIPromptProvider::class)
     ->shouldReceive('promptAI')
     ->with('name')
     ->andReturn('Jane Doe');
     
// Now when your application code calls fake()->promptAI('name')
// it will return 'Jane Doe' without making an actual API call
```

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher
- Composer

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/amazing-feature`)
3. Commit your Changes (`git commit -m 'Add some amazing feature'`)
4. Push to the Branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Credits

- Created by Jordan Price
- Documentation and README improvements by the community

## Support

If you encounter any problems or have any questions, please open an issue on GitHub.
