<?php

namespace JordanPrice\LaravelFakerAI;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Support\ServiceProvider;
use JordanPrice\LaravelFakerAI\Providers\FakerAIPromptProvider;
use JordanPrice\LaravelFakerAI\Console\InstallCommand;

class FakerAIServiceProvider extends ServiceProvider
{
    /**
     * The array of resolved Faker instances.
     *
     * @var array
     */
    protected static $fakers = [];

    public function register()
    {
        $this->registerFakerGenerator();
        $this->mergeConfigFrom(
            __DIR__.'/../config/faker-ai.php', 'faker-ai'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/faker-ai.php' => config_path('faker-ai.php'),
        ], 'faker-ai-config');

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Register the Faker generator with our custom providers.
     */
    protected function registerFakerGenerator(): void
    {
        $this->app->singleton(FakerGenerator::class, function ($app, $parameters) {
            $locale = $parameters['locale'] ?? $app['config']->get('app.faker_locale', 'en_US');

            if (!isset(static::$fakers[$locale])) {
                $fakerFactory = FakerFactory::create($locale);
                $fakerFactory->addProvider(new FakerAIPromptProvider($app));
                static::$fakers[$locale] = $fakerFactory;
            }

            static::$fakers[$locale]->unique(true);

            return static::$fakers[$locale];
        });

        // For usage with the `faker()` helper, which requires the locale to be set.
        $fakerLocale = $this->app['config']->get('app.faker_locale', 'en_US');
        $this->app->alias(FakerGenerator::class, FakerGenerator::class.':'.$fakerLocale);
    }
}
