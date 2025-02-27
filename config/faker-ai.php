<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used for AI
    | text generation. Support providers include 'ollama', 'openai',
    | 'anthropic', 'mistral'. You may set this to any of the supported providers.
    |
    */
    'default_provider' => env('FAKER_AI_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Default Models by Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default model for each provider that will be used
    | for AI generation. You may set these to any of the models available
    | through each respective provider.
    |
    */
    'default_models' => [
        'ollama' => env('FAKER_OLLAMA_MODEL', 'llama3'),
        'openai' => env('FAKER_OPENAI_MODEL', 'gpt-3.5-turbo'),
        'anthropic' => env('FAKER_ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
        'mistral' => env('FAKER_MISTRAL_MODEL', 'mistral-tiny'),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | This option controls the default system prompt that will be sent to the AI
    | before user prompts. This helps set the behavior and capabilities of the
    | AI assistant. You can customize this to better fit your use case.
    |
    */
    'default_system_prompt' => env('FAKER_AI_SYSTEM_PROMPT', 'You are a helpful assistant that generates fake data for testing purposes. Generate concise, realistic data without additional explanation or formatting.'),

    /*
    |--------------------------------------------------------------------------
    | Default Prompt Format
    |--------------------------------------------------------------------------
    |
    | This option controls the default prompt format used when generating
    | AI-based fake data. The %s placeholder will be replaced with the 
    | requested field name.
    |
    */
    'default_prompt' => env('FAKER_AI_PROMPT', 'Generate a '),

    /*
    |--------------------------------------------------------------------------
    | Default Context-Based Prompt Format
    |--------------------------------------------------------------------------
    |
    | This option controls the default prompt format used when generating
    | AI-based fake data with context. The first %s placeholder will be replaced
    | with the requested field name, and the second %s will be replaced with the
    | context information.
    |
    */
    'default_context_prompt' => env('FAKER_AI_CONTEXT_PROMPT', 'Generate a %s based on this context: %s'),

    /*
    |--------------------------------------------------------------------------
    | Enable Caching
    |--------------------------------------------------------------------------
    |
    | This option determines whether responses from the AI should be cached
    | to improve performance and reduce API costs. When enabled, identical
    | prompts will retrieve cached responses rather than making new API calls.
    |
    */
    'enable_cache' => env('FAKER_AI_ENABLE_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | This option controls how long (in minutes) the responses from the AI
    | should be cached. After this time period, a new API call will be made
    | for the same prompt.
    |
    */
    'cache_ttl' => env('FAKER_AI_CACHE_TTL', 1440), // 24 hours
];
