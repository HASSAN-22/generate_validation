<?php

namespace GenerateValidation;

use Illuminate\Support\ServiceProvider;
use GenerateValidation\Commands\MakeModelValidationRequest;

/**
 * Class GenerateValidationServiceProvider
 *
 * This Service Provider is responsible for registering the package's
 * services and commands with the Laravel application.
 *
 * @package GenerateValidation
 */
class GenerateValidationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered.
     * It's a great place to register console commands, publish configuration files,
     * or set up event listeners.
     *
     * @return void
     */
    public function boot(): void
    {
        // A. Check if the application is running in a console environment.
        // This is a common practice to ensure that artisan commands are
        // only registered when they are needed.
        if ($this->app->runningInConsole()) {
            // B. Register the custom artisan command.
            $this->commands([
                MakeModelValidationRequest::class,
            ]);
        }
    }
}