# Ollama Integration Guide

This guide provides detailed instructions for integrating Laravel Faker AI with Ollama for local AI generation.

## What is Ollama?

[Ollama](https://ollama.ai) is an open-source project that allows you to run large language models locally on your machine. It provides a simple API to interact with these models without requiring external API keys or internet connectivity.

## Advantages of Using Ollama with Laravel Faker AI

- **Free to use**: No API costs or usage limits
- **Privacy**: All data stays on your local machine
- **Works offline**: No internet connection required after initial setup
- **Easy to customize**: Configure models to your specific needs
- **Great for development**: Perfect for local development environments

## Installation

### 1. Install Ollama

Follow the installation instructions on the [Ollama website](https://ollama.ai) for your operating system:

**macOS**:
- Download the macOS application from the website
- Open the application and follow the installation prompts

**Linux**:
```bash
curl -fsSL https://ollama.ai/install.sh | sh
```

**Windows (WSL)**:
- Install WSL if not already installed
- Follow the Linux installation instructions

### 2. Start Ollama

After installation, start the Ollama service:

- On macOS, launch the Ollama application
- On Linux, the service should start automatically after installation

Verify Ollama is running by checking the API endpoint:
```bash
curl http://localhost:11434/api/tags
```

### 3. Download a Model

Pull a model that you want to use with Laravel Faker AI:

```bash
ollama pull llama3
```

Popular models include:
- `llama3` - Meta's Llama 3 model, good balance of quality and speed
- `llama3:8b` - Smaller variant of Llama 3
- `mistral` - Mistral AI's model
- `deepseek` - DeepSeek coder model, good for code generation

Check your available models:
```bash
ollama list
```

### 4. Install Required PHP Packages

In your Laravel project, install the Laravel Faker AI package and the required Prism dependency:

```bash
composer require jordan-price/laravel-faker-ai
composer require echolabsdev/prism
```

### 5. Configure Laravel Faker AI

Run the installation command:

```bash
php artisan faker-ai:install
```

Update your `.env` file with the following configuration:

```
FAKER_AI_PROVIDER=ollama
FAKER_OLLAMA_MODEL=llama3
FAKER_AI_ENABLE_CACHE=true
FAKER_AI_CACHE_TTL=1440
```

## Testing Your Integration

### Simple Test Script

Create a simple test script to verify your Ollama integration:

```php
<?php
// test-ollama.php

require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->boot();

echo "Testing Ollama integration...\n";

// Generate a product name
echo "Generating a tech product name:\n";
$productName = fake()->promptAI('productName', context: [
    'product_type' => 'technology',
    'industry' => 'consumer electronics'
]);
echo "Result: " . $productName . "\n\n";

// Generate a product description
echo "Generating a product description:\n";
$description = fake()->promptAI('productDescription', context: [
    'product_name' => $productName,
    'product_type' => 'technology',
    'industry' => 'consumer electronics'
]);
echo "Result: " . $description . "\n\n";

echo "Test completed successfully!";
```

Run the test script:
```bash
php test-ollama.php
```

### Using with Laravel Factories

Configure your model factories to use AI-generated content:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JordanPrice\LaravelFakerAI\Traits\HasAIContext;

class ProductFactory extends Factory
{
    use HasAIContext;
    
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'features' => implode(', ', fake()->words(5)),
            'price' => fake()->randomFloat(2, 99, 999),
            'sku' => strtoupper(fake()->bothify('???####')),
        ];
    }
    
    public function configure(): static
    {
        return $this->aiContext(['product_type' => 'general'])
                   ->aiFields(['name', 'description', 'features']);
    }
    
    public function tech(): static
    {
        return $this->aiContext([
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ]);
    }
    
    public function kitchen(): static
    {
        return $this->aiContext([
            'product_type' => 'kitchen',
            'industry' => 'home appliances'
        ]);
    }
}
```

Generate products using the factory:

```php
// Using ai-context for generation
$techProducts = Product::factory()
    ->tech()
    ->count(3)
    ->create([
        'name' => fn() => fake()->promptAI('productName', context: [
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ]),
        'description' => fn() => fake()->promptAI('productDescription', context: [
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ]),
        'features' => fn() => fake()->promptAI('productFeatures', context: [
            'product_type' => 'technology',
            'industry' => 'consumer electronics'
        ])
    ]);
```

## Troubleshooting

### Common Issues

1. **Ollama not running**
   - Error: "Failed to connect to localhost port 11434: Connection refused"
   - Solution: Ensure Ollama is running by checking `curl http://localhost:11434/api/tags`

2. **Model not found**
   - Error: "Model 'llama3' not found"
   - Solution: Pull the model using `ollama pull llama3` or check available models with `ollama list`

3. **Slow performance**
   - Issue: Generation takes a long time
   - Solutions:
     - Use a smaller model (e.g., `llama3:8b` instead of a larger variant)
     - Enable caching to avoid regenerating the same content
     - Consider hardware with GPU acceleration for better performance

4. **Memory issues**
   - Error: "Failed to load model: not enough memory"
   - Solutions:
     - Use a smaller model
     - Close other memory-intensive applications
     - Add more RAM to your system
     - Use swap space on Linux systems

### Debugging

If you're encountering issues, enable debug logging in your Laravel application:

```
LOG_LEVEL=debug
```

Then check your Laravel logs in `storage/logs/laravel.log` for more detailed error information.

## Advanced Configuration

### Custom System Prompts

You can customize the system prompts used by Laravel Faker AI by publishing the configuration file:

```bash
php artisan vendor:publish --tag=laravel-faker-ai-config
```

Then edit the `config/faker-ai.php` file to update the system prompts.

### Using with Selenium/Browser Testing

When running browser tests that involve database seeding with AI-generated content, you may want to use cached responses to speed up tests:

```php
// In your TestCase setup
public function setUp(): void
{
    parent::setUp();
    
    // Use a specific cache key for tests to avoid conflicts
    config(['faker-ai.cache_key_prefix' => 'test_faker_ai_']);
    
    // Ensure caching is enabled for tests
    config(['faker-ai.enable_cache' => true]);
}
```

## Performance Optimization

### Model Selection

Ollama supports various model sizes. For development purposes, smaller models are usually sufficient:

| Model Size | Memory Usage | Generation Speed | Quality |
|------------|--------------|------------------|---------|
| 3B-7B      | Low          | Fast             | Good    |
| 8B-13B     | Medium       | Medium           | Better  |
| 70B+       | High         | Slow             | Best    |

### Hardware Recommendations

For the best experience with Ollama:

- **Minimum**: 8GB RAM, modern multi-core CPU
- **Recommended**: 16GB+ RAM, modern CPU with 8+ cores
- **Optimal**: 32GB+ RAM, NVIDIA GPU with 8GB+ VRAM

## Contributing

If you discover issues or have suggestions for the Ollama integration, please open an issue on the [Laravel Faker AI GitHub repository](https://github.com/jordan-price/laravel-faker-ai).
