<?php

declare(strict_types=1);

namespace NirajKhadka\LaravelModuleGenerator;

use Illuminate\Support\ServiceProvider;
use NirajKhadka\LaravelModuleGenerator\Console\Commands\MakeModuleCommand;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array<class-string>
     */
    protected array $commands = [
        MakeModuleCommand::class,
    ];

    /**
     * Configuration files to be published.
     *
     * @var array<string, array<string, string>>
     */
    protected array $configFiles = [
        'module-generation-config' => [
            'source' => 'config.php',
            'destination' => 'module-generation-module.php',
        ],
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishConfigurations();
        $this->publishStubs();
        $this->registerCommands();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigurations();
    }

    /**
     * Publish configuration files.
     */
    protected function publishConfigurations(): void
    {
        foreach ($this->configFiles as $groupName => $config) {
            $this->publishes([
                __DIR__ . "/../config/{$config['source']}" => config_path($config['destination']),
            ], $groupName);
        }
    }

    /**
     * Publish stub files.
     */
    protected function publishStubs(): void
    {
        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/vendor/module-generator-stubs'),
        ], 'module-generator-stubs');
    }

    /**
     * Register commands when running in console.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Merge default configurations.
     */
    protected function mergeConfigurations(): void
    {
        foreach ($this->configFiles as $groupName => $config) {
            $this->mergeConfigFrom(
                __DIR__ . "/../config/{$config['source']}",
                str_replace('-config', '', $groupName),
            );
        }
    }
}
