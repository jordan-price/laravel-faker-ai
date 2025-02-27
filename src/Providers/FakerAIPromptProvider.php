<?php

namespace JordanPrice\LaravelFakerAI\Providers;

use Faker\Provider\Base;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Container\Container;

// Look for our mocks in tests/Mocks/PrismMocks.php
if (!class_exists('EchoLabs\Prism\Prism') && file_exists(__DIR__ . '/../../tests/Mocks/PrismMocks.php')) {
    require_once __DIR__ . '/../../tests/Mocks/PrismMocks.php';
}

class FakerAIPromptProvider extends Base
{
    /**
     * The application container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Create a new faker AI prompt provider.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @return void
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Get the provider enum from the provider name.
     *
     * @param string $provider
     * @return mixed
     */
    protected function getProviderEnum(string $provider)
    {
        return match ($provider) {
            'ollama' => \EchoLabs\Prism\Enums\Provider::Ollama,
            'openai' => \EchoLabs\Prism\Enums\Provider::OpenAI,
            'anthropic' => \EchoLabs\Prism\Enums\Provider::Anthropic,
            'mistral' => \EchoLabs\Prism\Enums\Provider::Mistral,
            default => \EchoLabs\Prism\Enums\Provider::Ollama,
        };
    }

    /**
     * Prompt the AI to generate a response based on the given term.
     *
     * @example $faker->promptAI('name')
     * @example $faker->promptAI('movieReview') // This will return a movie review
     * @example $faker->promptAI('productDescription', context: ['name' => 'Ergonomic Chair', 'category' => 'Office Furniture'])
     * @example $faker->promptAI('movieDescription', provider: 'openai', model: 'gpt-4') // Use a specific provider and model
     * @example $faker->promptAI('name', fallback: 'John Doe') // This defaults to 'John Doe' if an error occurs
     * @example $faker->promptAI('name', fallback: fn() => 'John Doe') // This defaults to 'John Doe' if an error occurs
     * @param string $term
     * @param string|null $provider
     * @param string|null $model
     * @param array $context
     * @param null|string|callable $fallback
     * @param bool $throwOnError
     * @return mixed
     */
    public function promptAI(
        string $term,
        ?string $provider = null,
        ?string $model = null,
        array $context = [],
        null|string|callable $fallback = null,
        bool $throwOnError = false
    ): mixed {
        // Get configuration from the app container
        $config = $this->getConfig();

        $provider = $provider ?? $config['default_provider'] ?? 'ollama';
        $providerEnum = $this->getProviderEnum($provider);
        $model = $model ?? ($config['default_models'][$provider] ?? null);

        // Generate a cache key based on the parameters and context
        $contextHash = !empty($context) ? md5(json_encode($context)) : '';
        $cacheKey = "faker-ai:{$provider}:{$model}:{$term}:{$contextHash}";

        // Check if caching is enabled and the response is cached
        if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'has') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Generate a formatted term for the prompt
            $formattedTerm = Str::camel($term);

            // Build prompt with context if provided
            if (!empty($context)) {
                $contextStr = $this->formatContextForPrompt($context);
                $prompt = ($config['default_context_prompt'] ?? 'Generate a %s based on this context: %s')
                    . ' Output the result without any explanation or formatting. A simple string, please.';
                $prompt = sprintf($prompt, $formattedTerm, $contextStr);
            } else {
                $prompt = ($config['default_prompt'] ?? 'Generate a ')
                    . $formattedTerm
                    . '() without any explanation or formatting. A simple string, please.';
            }

            // Use Prism to generate the response
            $response = \EchoLabs\Prism\Prism::text()
                ->using($providerEnum, $model)
                ->withSystemPrompt($config['default_system_prompt'] ?? '')
                ->withPrompt($prompt)
                ->generate();

            $result = trim($response->text);

            // Cache the result if caching is enabled
            if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'put')) {
                $ttl = $config['cache_ttl'] ?? 60;
                Cache::put($cacheKey, $result, now()->addMinutes($ttl));
            }

            return $result;
        } catch (\Exception $e) {
            if ($throwOnError) {
                throw $e;
            }

            if (method_exists(Log::class, 'error')) {
                Log::error("AI Faker Error: {$e->getMessage()}");
            }

            if (is_string($fallback)) {
                return $fallback;
            }

            if (is_callable($fallback)) {
                return $fallback();
            }

            return null;
        }
    }

    /**
     * Format the context array into a string for the prompt.
     *
     * @param array $context
     * @return string
     */
    protected function formatContextForPrompt(array $context): string
    {
        $formattedContext = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $formattedContext[] = "{$key}: {$value}";
        }

        return implode(', ', $formattedContext);
    }

    /**
     * Get the configuration for the AI faker.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        try {
            return $this->app['config']->get('faker-ai', [
                'default_provider' => 'ollama',
                'default_models' => [
                    'ollama' => 'llama3',
                    'openai' => 'gpt-3.5-turbo',
                    'anthropic' => 'claude-3-sonnet-20240229',
                    'mistral' => 'mistral-tiny',
                ],
                'default_system_prompt' => 'You are a helpful assistant that generates fake data for testing purposes.',
                'default_prompt' => 'Generate a ',
                'default_context_prompt' => 'Generate a %s based on this context: %s',
                'enable_cache' => false,
                'cache_ttl' => 60,
            ]);
        } catch (\Exception $e) {
            // Return default config for tests
            return [
                'default_provider' => 'ollama',
                'default_models' => [
                    'ollama' => 'llama3',
                    'openai' => 'gpt-3.5-turbo',
                    'anthropic' => 'claude-3-sonnet-20240229',
                    'mistral' => 'mistral-tiny',
                ],
                'default_system_prompt' => 'You are a helpful assistant that generates fake data for testing purposes.',
                'default_prompt' => 'Generate a ',
                'default_context_prompt' => 'Generate a %s based on this context: %s',
                'enable_cache' => false,
                'cache_ttl' => 60,
            ];
        }
    }

    /**
     * Generate an entire object with multiple AI-generated fields at once.
     *
     * @example $faker->promptAIObject(['description', 'features', 'metaDescription'], ['name' => 'Ergonomic Chair', 'category' => 'Office Furniture'])
     * @example $faker->promptAIObject(['bio', 'interests', 'quote'], ['name' => 'Jane Smith', 'age' => 32], provider: 'openai')
     * @example $faker->promptAIObject(['name', 'email', 'phone'], ['company' => 'ABC Inc.'], fallbacks: ['name' => 'John Doe'])
     * @param array $fields
     * @param array $context
     * @param string|null $provider
     * @param string|null $model
     * @param array|null $fallbacks
     * @param bool $throwOnError
     * @return array
     */
    public function promptAIObject(
        array $fields,
        array $context = [],
        ?string $provider = null,
        ?string $model = null,
        ?array $fallbacks = null,
        bool $throwOnError = false
    ): array {
        // Get configuration from the app container
        $config = $this->getConfig();

        $provider = $provider ?? $config['default_provider'] ?? 'ollama';
        $providerEnum = $this->getProviderEnum($provider);
        $model = $model ?? ($config['default_models'][$provider] ?? null);

        // Generate a cache key based on the parameters and context
        $fieldsKey = md5(json_encode($fields));
        $contextHash = !empty($context) ? md5(json_encode($context)) : '';
        $cacheKey = "faker-ai-object:{$provider}:{$model}:{$fieldsKey}:{$contextHash}";

        // Check if caching is enabled and the response is cached
        if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'has') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Format fields for the prompt
            $formattedFields = array_map(function ($field) {
                return Str::camel($field);
            }, $fields);

            // Build the prompt for multi-field generation
            $fieldsList = implode(', ', $formattedFields);

            $prompt = "Generate the following fields for an object: {$fieldsList}.\n\n";

            if (!empty($context)) {
                $contextStr = $this->formatContextForPrompt($context);
                $prompt .= "Use this context information: {$contextStr}.\n\n";
            }

            $prompt .= "Return the results as a valid JSON object with the field names as keys.";

            // Use Prism to generate the response
            $response = \EchoLabs\Prism\Prism::text()
                ->using($providerEnum, $model)
                ->withSystemPrompt($config['default_system_prompt'] ?? '')
                ->withPrompt($prompt)
                ->generate();

            $result = trim($response->text);

            // Extract the JSON from the response
            $jsonMatches = [];
            preg_match('/\{.*\}/s', $result, $jsonMatches);

            if (!empty($jsonMatches)) {
                $result = $jsonMatches[0];
            }

            // Decode the JSON
            $decodedResult = json_decode($result, true);

            // If JSON is invalid, handle fallbacks or throw exception
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($throwOnError) {
                    throw new \Exception("Failed to decode JSON response: " . json_last_error_msg());
                }

                // Use fallbacks if available, otherwise return empty array
                return $this->getFallbackValues($fields, $fallbacks);
            }

            // Apply any fallbacks for missing fields
            foreach ($fields as $field) {
                $camelField = Str::camel($field);
                if (!isset($decodedResult[$camelField]) && !isset($decodedResult[$field])) {
                    $fieldKey = isset($fallbacks[$field]) ? $field : $camelField;
                    $decodedResult[$fieldKey] = $this->getFallbackValue($fieldKey, $fallbacks);
                }
            }

            // Cache the result if caching is enabled
            if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'put')) {
                $ttl = $config['cache_ttl'] ?? 60;
                Cache::put($cacheKey, $decodedResult, now()->addMinutes($ttl));
            }

            return $decodedResult;
        } catch (\Exception $e) {
            if ($throwOnError) {
                throw $e;
            }

            if (method_exists(Log::class, 'error')) {
                Log::error("AI Faker Object Error: {$e->getMessage()}");
            }

            // Return fallbacks or empty array
            return $this->getFallbackValues($fields, $fallbacks);
        }
    }

    /**
     * Get fallback values for multiple fields.
     *
     * @param array $fields
     * @param array|null $fallbacks
     * @return array
     */
    protected function getFallbackValues(array $fields, ?array $fallbacks): array
    {
        $result = [];

        foreach ($fields as $field) {
            $result[$field] = $this->getFallbackValue($field, $fallbacks);
        }

        return $result;
    }

    /**
     * Get fallback value for a specific field.
     *
     * @param string $field
     * @param array|null $fallbacks
     * @return mixed
     */
    protected function getFallbackValue(string $field, ?array $fallbacks): mixed
    {
        if (!empty($fallbacks) && isset($fallbacks[$field])) {
            $fallback = $fallbacks[$field];
            return is_callable($fallback) ? $fallback() : $fallback;
        }

        // Default fallbacks based on common field names
        return match (Str::camel($field)) {
            'name', 'title' => 'Sample Name',
            'description', 'content', 'text' => 'Sample description text.',
            'summary', 'excerpt' => 'Brief sample text.',
            'features' => 'Feature 1, Feature 2, Feature 3',
            'metaDescription', 'seoDescription' => 'Sample meta description for SEO purposes.',
            'bio', 'biography' => 'Sample biography text.',
            'interests', 'hobbies' => 'Reading, Travel, Technology',
            'pros' => 'Good quality, Fast delivery, Excellent support',
            'cons' => 'Limited options, Higher price',
            'review', 'feedback' => 'This is a sample review.',
            default => '',
        };
    }

    /**
     * Create a complete object with AI-generated properties based on minimal information.
     *
     * @example $faker->createAIObject('Product', ['category' => 'Electronics']) // Returns a complete product object
     * @example $faker->createAIObject('BlogPost', ['topic' => 'Technology', 'audience' => 'beginners'])
     * @example $faker->createAIObject('User', ['role' => 'admin', 'departmentId' => 3])
     * @example $faker->createAIObject('Product', ['name' => 'Sample Product'], fallbacks: ['description' => 'Sample product description'])
     * @param string $objectType
     * @param array $seedInfo
     * @param array|null $requiredFields
     * @param string|null $provider
     * @param string|null $model
     * @param array|null $fallbacks
     * @param bool $throwOnError
     * @return array
     */
    public function createAIObject(
        string $objectType,
        array $seedInfo = [],
        ?array $requiredFields = null,
        ?string $provider = null,
        ?string $model = null,
        ?array $fallbacks = null,
        bool $throwOnError = false
    ): array {
        // Get configuration from the app container
        $config = $this->getConfig();

        $provider = $provider ?? $config['default_provider'] ?? 'ollama';
        $providerEnum = $this->getProviderEnum($provider);
        $model = $model ?? ($config['default_models'][$provider] ?? null);

        // Generate a cache key based on the parameters and seed info
        $objectKey = strtolower($objectType);
        $seedInfoHash = !empty($seedInfo) ? md5(json_encode($seedInfo)) : '';
        $requiredFieldsHash = !empty($requiredFields) ? md5(json_encode($requiredFields)) : '';
        $cacheKey = "faker-ai-create:{$provider}:{$model}:{$objectKey}:{$seedInfoHash}:{$requiredFieldsHash}";

        // Check if caching is enabled and the response is cached
        if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'has') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Build the prompt for object creation
            $prompt = "Create a complete {$objectType} object with realistic properties";

            if (!empty($seedInfo)) {
                $seedStr = $this->formatContextForPrompt($seedInfo);
                $prompt .= " based on this information: {$seedStr}";
            }

            if (!empty($requiredFields)) {
                $requiredFieldsStr = implode(', ', $requiredFields);
                $prompt .= ". The object MUST include these fields: {$requiredFieldsStr}";
            }

            $prompt .= ".\n\nReturn the complete object as a valid JSON object with all necessary properties for a {$objectType}.";
            // Use Prism to generate the response
            $response = \EchoLabs\Prism\Prism::text()
                ->using($providerEnum, $model)
                ->withSystemPrompt($config['default_system_prompt'] ?? '')
                ->withPrompt($prompt)
                ->generate();

            $result = trim($response->text);

            // Extract the JSON from the response
            $jsonMatches = [];
            preg_match('/\{.*\}/s', $result, $jsonMatches);

            if (!empty($jsonMatches)) {
                $result = $jsonMatches[0];
            }

            // Decode the JSON
            $decodedResult = json_decode($result, true);

            // If JSON is invalid, handle fallbacks or throw exception
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($throwOnError) {
                    throw new \Exception("Failed to decode JSON response: " . json_last_error_msg());
                }

                // Use fallbacks if available or return a basic object
                return $this->getBasicObject($objectType, $seedInfo, $requiredFields, $fallbacks);
            }

            // Make sure all required fields are present
            if (!empty($requiredFields)) {
                foreach ($requiredFields as $field) {
                    if (!isset($decodedResult[$field])) {
                        $decodedResult[$field] = $this->getFallbackValue($field, $fallbacks);
                    }
                }
            }

            // Cache the result if caching is enabled
            if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'put')) {
                $ttl = $config['cache_ttl'] ?? 60;
                Cache::put($cacheKey, $decodedResult, now()->addMinutes($ttl));
            }

            return $decodedResult;
        } catch (\Exception $e) {
            if ($throwOnError) {
                throw $e;
            }

            if (method_exists(Log::class, 'error')) {
                Log::error("AI Object Creation Error: {$e->getMessage()}");
            }

            // Return a basic object with fallbacks
            return $this->getBasicObject($objectType, $seedInfo, $requiredFields, $fallbacks);
        }
    }

    /**
     * Create a basic object with fallbacks when AI generation fails.
     *
     * @param string $objectType
     * @param array $seedInfo
     * @param array|null $requiredFields
     * @param array|null $fallbacks
     * @return array
     */
    protected function getBasicObject(
        string $objectType,
        array $seedInfo = [],
        ?array $requiredFields = null,
        ?array $fallbacks = null
    ): array {
        $result = $seedInfo; // Start with seed info

        // Add required fields with fallbacks
        if (!empty($requiredFields)) {
            foreach ($requiredFields as $field) {
                if (!isset($result[$field])) {
                    $result[$field] = $this->getFallbackValue($field, $fallbacks);
                }
            }
        }

        // Add some basic fields based on object type
        $objectType = Str::lower($objectType);

        switch ($objectType) {
            case 'product':
                $result['name'] = $result['name'] ?? 'Sample Product';
                $result['description'] = $result['description'] ?? 'A sample product description.';
                $result['price'] = $result['price'] ?? 99.99;
                break;

            case 'user':
                $result['name'] = $result['name'] ?? 'John Doe';
                $result['email'] = $result['email'] ?? 'user@example.com';
                $result['role'] = $result['role'] ?? 'user';
                break;

            case 'blogpost':
            case 'post':
            case 'article':
                $result['title'] = $result['title'] ?? 'Sample Article';
                $result['content'] = $result['content'] ?? 'This is a sample article content.';
                $result['author'] = $result['author'] ?? 'Anonymous';
                break;

            default:
                // Add some generic fields
                $result['name'] = $result['name'] ?? 'Sample ' . ucwords($objectType);
                $result['description'] = $result['description'] ?? 'Sample description for ' . ucwords($objectType);
        }

        return $result;
    }

    /**
     * Create multiple related objects at once, maintaining context between them.
     *
     * @example $faker->createAIBatch('Product', 5, ['category' => 'Electronics']) // Returns 5 related products
     * @example $faker->createAIBatch('Review', 3, ['productName' => 'iPhone', 'rating' => 4.5])
     * @example $faker->createAIBatch('Brand', 4, ['industry' => 'Technology', 'marketPosition' => 'premium'])
     * @param string $objectType
     * @param int $count
     * @param array $seedInfo
     * @param array|null $requiredFields
     * @param string|null $provider
     * @param string|null $model
     * @param array|null $fallbacks
     * @param bool $throwOnError
     * @return array
     */
    public function createAIBatch(
        string $objectType,
        int $count,
        array $seedInfo = [],
        ?array $requiredFields = null,
        ?string $provider = null,
        ?string $model = null,
        ?array $fallbacks = null,
        bool $throwOnError = false
    ): array {
        // Get configuration from the app container
        $config = $this->getConfig();

        $provider = $provider ?? $config['default_provider'] ?? 'ollama';
        $providerEnum = $this->getProviderEnum($provider);
        $model = $model ?? ($config['default_models'][$provider] ?? null);

        // Generate a cache key based on the parameters and seed info
        $objectKey = strtolower($objectType);
        $seedInfoHash = !empty($seedInfo) ? md5(json_encode($seedInfo)) : '';
        $requiredFieldsHash = !empty($requiredFields) ? md5(json_encode($requiredFields)) : '';
        $cacheKey = "faker-ai-batch:{$provider}:{$model}:{$objectKey}:{$count}:{$seedInfoHash}:{$requiredFieldsHash}";

        // Check if caching is enabled and the response is cached
        if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'has') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Build the prompt for batch object creation
            $prompt = "Create {$count} related {$objectType} objects with realistic properties";

            if (!empty($seedInfo)) {
                $seedStr = $this->formatContextForPrompt($seedInfo);
                $prompt .= " based on this information: {$seedStr}";
            }

            if (!empty($requiredFields)) {
                $requiredFieldsStr = implode(', ', $requiredFields);
                $prompt .= ". Each object MUST include these fields: {$requiredFieldsStr}";
            }

            $prompt .= ".\n\n";
            $prompt .= "Make sure these {$count} {$objectType}s are contextually related and make sense together as a collection. ";
            $prompt .= "Each should be unique but fit a coherent theme based on the provided information.\n\n";
            $prompt .= "Return the result as a valid JSON array containing {$count} objects.";

            // Use Prism to generate the response
            $response = \EchoLabs\Prism\Prism::text()
                ->using($providerEnum, $model)
                ->withSystemPrompt($config['default_system_prompt'] ?? '')
                ->withPrompt($prompt)
                ->generate();

            $result = trim($response->text);

            // Extract the JSON from the response
            $jsonMatches = [];
            preg_match('/\[.*\]/s', $result, $jsonMatches);

            if (!empty($jsonMatches)) {
                $result = $jsonMatches[0];
            }

            // Decode the JSON
            $decodedResult = json_decode($result, true);

            // If JSON is invalid, handle fallbacks or throw exception
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($throwOnError) {
                    throw new \Exception("Failed to decode JSON response: " . json_last_error_msg());
                }

                // Use fallbacks to create a batch of basic objects
                return $this->getBasicBatch($objectType, $count, $seedInfo, $requiredFields, $fallbacks);
            }

            // Check if we got the right structure (array of objects)
            if (!is_array($decodedResult) || empty($decodedResult)) {
                if ($throwOnError) {
                    throw new \Exception("AI did not return an array of objects");
                }

                return $this->getBasicBatch($objectType, $count, $seedInfo, $requiredFields, $fallbacks);
            }

            // Ensure we have exactly the requested number of objects
            $objects = array_slice($decodedResult, 0, $count);
            while (count($objects) < $count) {
                $objects[] = $this->getBasicObject($objectType, $seedInfo, $requiredFields, $fallbacks);
            }

            // Make sure all required fields are present in each object
            if (!empty($requiredFields)) {
                foreach ($objects as &$object) {
                    foreach ($requiredFields as $field) {
                        if (!isset($object[$field])) {
                            $object[$field] = $this->getFallbackValue($field, $fallbacks);
                        }
                    }
                }
                unset($object); // Break the reference
            }

            // Cache the result if caching is enabled
            if (($config['enable_cache'] ?? false) && method_exists(Cache::class, 'put')) {
                $ttl = $config['cache_ttl'] ?? 60;
                Cache::put($cacheKey, $objects, now()->addMinutes($ttl));
            }

            return $objects;
        } catch (\Exception $e) {
            if ($throwOnError) {
                throw $e;
            }

            if (method_exists(Log::class, 'error')) {
                Log::error("AI Batch Creation Error: {$e->getMessage()}");
            }

            // Return a batch of basic objects with fallbacks
            return $this->getBasicBatch($objectType, $count, $seedInfo, $requiredFields, $fallbacks);
        }
    }

    /**
     * Create a batch of basic objects with fallbacks when AI generation fails.
     *
     * @param string $objectType
     * @param int $count
     * @param array $seedInfo
     * @param array|null $requiredFields
     * @param array|null $fallbacks
     * @return array
     */
    protected function getBasicBatch(
        string $objectType,
        int $count,
        array $seedInfo = [],
        ?array $requiredFields = null,
        ?array $fallbacks = null
    ): array {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            // Create variations in the seed info to avoid identical objects
            $currentSeed = $seedInfo;
            if (!empty($currentSeed)) {
                // Add an index to any string values to make them unique
                foreach ($currentSeed as $key => $value) {
                    if (is_string($value)) {
                        $currentSeed[$key] = $value . ' ' . ($i + 1);
                    }
                }
            }

            $result[] = $this->getBasicObject($objectType, $currentSeed, $requiredFields, $fallbacks);
        }

        return $result;
    }
}
