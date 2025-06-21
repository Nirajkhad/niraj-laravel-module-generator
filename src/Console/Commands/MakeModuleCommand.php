<?php

declare(strict_types=1);

namespace Niraj\LaravelModuleGenerator\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'module:make {name : The name of the module}';

    protected $description = 'Create a new module with controller, service, actions, DTO, requests, model, and migration';

    private const STUB_DIRECTORY = 'niraj/laravel-module-generator';

    private const ACTIONS_PATH_PATTERN = 'Actions{moduleNamespaceDir}/{moduleName}';

    private const REQUESTS_PATH_PATTERN = 'Http/Requests{moduleNamespaceDir}/{moduleName}';

    private const MODULE_COMPONENTS = [
        'controller' => [
            'path' => 'Http/Controllers{moduleNamespaceDir}',
            'suffix' => 'Controller',
            'class' => '{moduleName}Controller',
            'stub' => 'controller',
        ],
        'resource' => [
            'path' => 'Http/Resources',
            'class' => '{moduleName}Resource',
            'stub' => 'resource',
        ],
        'service' => [
            'path' => 'Services{moduleNamespaceDir}',
            'suffix' => 'Service',
            'class' => '{moduleName}Service',
            'stub' => 'service',
        ],
        'dto' => [
            'path' => 'Dtos{moduleNamespaceDir}',
            'suffix' => 'Dto',
            'class' => '{moduleName}Dto',
            'stub' => 'dto',
        ],
        'index-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'IndexAction',
            'stub' => 'index-action',
        ],
        'store-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'StoreAction',
            'stub' => 'store-action',
        ],
        'update-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'UpdateAction',
            'stub' => 'update-action',
        ],
        'delete-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'DeleteAction',
            'stub' => 'delete-action',
        ],
        'store-request' => [
            'path' => self::REQUESTS_PATH_PATTERN,
            'class' => 'StoreRequest',
            'stub' => 'store-request',
        ],
        'update-request' => [
            'path' => self::REQUESTS_PATH_PATTERN,
            'class' => 'UpdateRequest',
            'stub' => 'update-request',
        ],
        'index-request' => [
            'path' => self::REQUESTS_PATH_PATTERN,
            'class' => 'IndexRequest',
            'stub' => 'index-request',
        ],
        'model' => [
            'path' => 'Models{moduleNamespaceDir}',
            'class' => '{moduleName}',
            'stub' => 'model',
        ],
        'migration' => [
            'path' => '../database/migrations',
            'class' => '{timestamp}_create_{moduleNamePluralLower}_table',
            'stub' => 'migration',
        ],
    ];

    private array $stubPaths = [];

    public function handle(): int
    {
        $status = self::SUCCESS;

        try {
            $moduleName = (string) $this->argument('name');

            if (!$this->validateModuleName($moduleName)) {
                $status = self::FAILURE;
            } else {
                $namingFormats = $this->prepareNamingFormats($moduleName);

                $this->initializeStubPaths();

                $this->info("Creating syncing module for {$namingFormats['moduleName']}...");

                if (!$this->createModuleComponents($namingFormats)) {
                    $status = self::FAILURE;
                } else {
                    $this->displaySuccessMessage($namingFormats['moduleName']);
                }
            }
        } catch (Exception $e) {
            $this->error("Error creating module: {$e->getMessage()}");
            $this->error("File: {$e->getFile()}: {$e->getLine()}");
            $status = self::FAILURE;
        }

        return $status;
    }

    private function displaySuccessMessage(string $moduleName): void
    {
        $this->info("Successfully created {$moduleName} syncing module!");
        $this->info("\nNext steps:");
        $this->line("  • Run: php artisan migrate");
        $this->line("  • Add your API or web routes to routes/web.php or routes/api.php.");
    }

    private function validateModuleName(string $moduleName): bool
    {
        if (empty($moduleName)) {
            $this->error('Module name cannot be empty.');
            return false;
        }

        $actualModuleName = $this->extractActualModuleName($moduleName);

        if (!preg_match('/^\w+$/', $actualModuleName)) {
            $this->error('Module name must start with a letter and can only contain alphanumeric characters and underscores.');
            return false;
        }

        return true;
    }

    private function extractActualModuleName(string $moduleName): string
    {
        $parts = explode('/', $moduleName);
        return end($parts);
    }

    private function prepareNamingFormats(string $moduleName): array
    {
        $nameParts = explode('/', $moduleName);
        $actualModuleName = end($nameParts);
        $moduleNamespace = count($nameParts) > 1 ? implode('', array_slice($nameParts, 0, -1)) : '';

        $moduleNamespaceSegment = $moduleNamespace ? '' . $moduleNamespace : '';
        $moduleNamespaceDir = $moduleNamespace ? '/' . str_replace('', '/', $moduleNamespace) : '';

        return [
            'moduleName' => Str::singular(Str::studly($actualModuleName)),
            'moduleNameLower' => Str::camel(Str::singular(Str::studly($actualModuleName))),
            'moduleNamePlural' => Str::plural(Str::studly($actualModuleName)),
            'moduleNamePluralLower' => Str::camel(Str::plural(Str::studly($actualModuleName))),
            'moduleNamespace' => $moduleNamespace,
            'moduleNamespaceSegment' => $moduleNamespaceSegment,
            'moduleNamespaceDir' => $moduleNamespaceDir,
            'timestamp' => now()->format('Y_m_d_His'),
        ];
    }

    private function initializeStubPaths(): void
    {
        $publishedStubPath = base_path('stubs/' . self::STUB_DIRECTORY);

        foreach (self::MODULE_COMPONENTS as $component => $config) {
            $stubName = $config['stub'] ?? $component;
            $publishedStubFile = "{$publishedStubPath}/$stubName.stub";
            $packageStubFile = __DIR__ . "/../../../stubs/$stubName.stub";

            $this->stubPaths[$stubName] = File::exists($publishedStubFile)
                ? $publishedStubFile
                : $packageStubFile;
        }
    }

    private function createModuleComponents(array $namingFormats): bool
    {
        $success = true;

        foreach (self::MODULE_COMPONENTS as $componentName => $config) {
            if (!$this->createModuleComponent($config, $namingFormats, $componentName)) {
                $success = false;
            }
        }

        return $success;
    }

    private function createModuleComponent(array $config, array $namingFormats, string $componentName): bool
    {
        $dirPath = $this->resolvePath($config['path'], $namingFormats);

        $className = str_replace(
            ['{moduleName}', '{moduleNamePluralLower}', '{timestamp}'],
            [
                $namingFormats['moduleName'],
                $namingFormats['moduleNamePluralLower'],
                $namingFormats['timestamp'],
            ],
            $config['class'],
        );

        $filePath = "{$dirPath}/$className.php";

        // Special handling for migration files
        if ($componentName === 'migration') {
            return $this->handleMigrationCreation($config, $namingFormats, $className, $dirPath);
        }

        if (File::exists($filePath) && !$this->confirmOverwrite($className)) {
            $this->info("Skipped $className creation.");
            return true;
        }

        $this->ensureDirectoryExists($dirPath);

        return $this->createFileFromStub(
            $config['stub'],
            $filePath,
            $namingFormats,
            $className,
        );
    }

    private function handleMigrationCreation(array $config, array $namingFormats, string $className, string $dirPath): bool
    {
        // Check if a migration with similar name already exists
        $migrationPattern = "*_create_{$namingFormats['moduleNamePluralLower']}_table.php";
        $existingMigrations = File::glob($dirPath . '/' . $migrationPattern);

        if (!empty($existingMigrations)) {
            $existingMigrationFile = basename($existingMigrations[0]);
            $existingMigrationName = pathinfo($existingMigrationFile, PATHINFO_FILENAME);
            
            if (!$this->confirmOverwrite("migration for {$namingFormats['moduleNamePluralLower']} table (existing: {$existingMigrationName})")) {
                $this->info("Skipped migration creation.");
                return true;
            }

            // Remove existing migration file
            File::delete($existingMigrations[0]);
            $this->info("Removed existing migration: {$existingMigrationFile}");
        }

        $filePath = "{$dirPath}/$className.php";
        $this->ensureDirectoryExists($dirPath);

        return $this->createFileFromStub(
            $config['stub'],
            $filePath,
            $namingFormats,
            $className,
        );
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    private function resolvePath(string $path, array $namingFormats): string
    {
        return str_replace(
            ['{moduleName}', '{moduleNamespaceDir}'],
            [
                $namingFormats['moduleName'],
                $namingFormats['moduleNamespaceDir'],
            ],
            app_path($path),
        );
    }

    private function confirmOverwrite(string $fileName): bool
    {
        return $this->confirm("The {$fileName} already exists. Do you want to replace it?");
    }

    private function createFileFromStub(string $stubName, string $targetPath, array $replacements, string $fileName): bool
    {
        try {
            $stubPath = $this->stubPaths[$stubName] ?? '';

            if (!File::exists($stubPath)) {
                $this->error("Stub file not found: $stubPath");
                return false;
            }

            $stub = File::get($stubPath);
            $processedContent = $this->processStubContent($stub, $replacements);

            File::makeDirectory(dirname($targetPath), 0755, true, true);
            File::put($targetPath, $processedContent);

            $this->info("Created {$fileName}: {$targetPath}");

            return true;
        } catch (Exception $e) {
            $this->error("Failed to create {$fileName}: {$e->getMessage()}");
            return false;
        }
    }

    private function processStubContent(string $content, array $replacements): string
    {
        foreach ($replacements as $search => $replace) {
            $content = str_replace('{{ ' . $search . ' }}', $replace, $content);
        }

        return $content;
    }
}