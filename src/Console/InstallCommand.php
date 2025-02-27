<?php

namespace JordanPrice\LaravelFakerAI\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faker-ai:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and publish the Faker AI configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->comment('Publishing Faker AI Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'faker-ai-config']);

        $this->info('Faker AI was installed successfully.');
        $this->info('Make sure you have set up a compatible AI provider in your .env file:');
        $this->info('FAKER_AI_PROVIDER=ollama');
        $this->info('FAKER_OLLAMA_MODEL=llama3');
        
        return 0;
    }
}
