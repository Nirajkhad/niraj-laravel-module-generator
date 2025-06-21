<?php

declare(strict_types=1);

namespace Niraj\LaravelModuleGenerator\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'module:make {name : The name of the module} 
                           {--force : Overwrite existing files without confirmation}
                           {--no-migration : Skip migration generation}
                           {--api : Generate API-focused structure}';

    protected $description = 'Generate a complete CRUD module with controller, service, actions, DTO, requests, model, migration, and resource';

    private const STUB_DIRECTORY = 'niraj/laravel-module-generator';

    private const ACTIONS_PATH_PATTERN = 'Actions{moduleNamespaceDir}/{moduleName}';
    private const REQUESTS_PATH_PATTERN = 'Http/Requests{moduleNamespaceDir}/{moduleName}';

    private const MODULE_COMPONENTS = [
        'controller' => [
            'path' => 'Http/Controllers{moduleNamespaceDir}',
            'class' => '{moduleName}Controller',
            'stub' => 'controller',
            'description' => 'API Controller',
        ],
        'resource' => [
            'path' => 'Http/Resources{moduleNamespaceDir}',
            'class' => '{moduleName}Resource',
            'stub' => 'resource',
            'description' => 'API Resource',
        ],
        'service' => [
            'path' => 'Services{moduleNamespaceDir}',
            'class' => '{moduleName}Service',
            'stub' => 'service',
            'description' => 'Business Logic Service',
        ],
        'dto' => [
            'path' => 'Dtos{moduleNamespaceDir}',
            'class' => '{moduleName}Dto',
            'stub' => 'dto',
            'description' => 'Data Transfer Object',
        ],
        'index-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'IndexAction',
            'stub' => 'index-action',
            'description' => 'List Action',
        ],
        'store-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'StoreAction',
            'stub' => 'store-action',
            'description' => 'Create Action',
        ],
        'update-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'UpdateAction',
            'stub' => 'update-action',
            'description' => 'Update Action',
        ],
        'delete-action' => [
            'path' => self::ACTIONS_PATH_PATTERN,
            'class' => 'DeleteAction',
            'stub' => 'delete-action',
            'description' => 'Delete Action',
        ],
        'store-request' => [
            'path' => self::REQUESTS_PATH_PATTERN,
            'class' => 'StoreRequest',
            'stub' => 'store-request',
            'description' => 'Store Form Request',
        ],
        'update-request' => [
            'path' => self::REQUESTS_PATH_PATTERN,
            'class' => 'UpdateRequest',
            'stub' => 'update-request',
            'description' => 'Update Form Request',
        ],
        'index-request' => [
            'path' => self::REQUESTS_PATH_PATTERN,
            'class' => 'IndexRequest',
            'stub' => 'index-request',
            'description' => 'Index Form Request',
        ],
        'model' => [
            'path' => 'Models{moduleNamespaceDir}',
            'class' => '{moduleName}',
            'stub' => 'model',
            'description' => 'Eloquent Model',
        ],
        'migration' => [
            'path' => '../database/migrations',
            'class' => '{timestamp}_create_{moduleNamePluralLower}_table',
            'stub' => 'migration',
            'description' => 'Database Migration',
            'skip_condition' => 'no-migration',
        ],
    ];

    private array $stubPaths = [];
    private array $createdFiles = [];

    public function handle(): int
    {
        try {
            $moduleName = (string) $this->argument('name');

            if (!$this->validateModuleName($moduleName)) {
                return self::FAILURE;
            }

            $namingFormats = $this->prepareNamingFormats($moduleName);
            $this->initializeStubPaths();

            $this->info("🚀 Generating CRUD module: {$namingFormats['moduleName']}");
            $this->newLine();

            if (!$this->createModuleComponents($namingFormats)) {
                return self::FAILURE;
            }

            $this->displaySuccessMessage($namingFormats);
            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Error creating module: {$e->getMessage()}");
            $this->error("📍 Location: {$e->getFile()}:{$e->getLine()}");
            return self::FAILURE;
        }
    }

    private function displaySuccessMessage(array $namingFormats): void
    {
        $this->newLine();
        $this->info("✅ Successfully created {$namingFormats['moduleName']} CRUD module!");

        $this->newLine();
        $this->comment('📁 Generated files:');
        foreach ($this->createdFiles as $file) {
            $this->line("   • {$file}");
        }

        $this->newLine();
        $this->comment('🔧 Next steps:');
        if (!$this->option('no-migration')) {
            $this->line('   • Run: php artisan migrate');
        }
        $this->line('   • Add routes to routes/api.php or routes/web.php');
        $this->line('   • Update the DTO and Form Requests with your fields');
        $this->line('   • Customize the generated actions as needed');

        $this->newLine();
        $this->comment('📖 Example route:');
        $routeName = Str::kebab($namingFormats['moduleNamePlural']);
        $this->line("   Route::apiResource('{$routeName}', {$namingFormats['moduleName']}Controller::class);");
    }

    private function validateModuleName(string $moduleName): bool
    {
        if (empty($moduleName)) {
            $this->error('❌ Module name cannot be empty.');
            return false;
        }

        $actualModuleName = $this->extractActualModuleName($moduleName);

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $actualModuleName)) {
            $this->error('❌ Module name must start with a letter and can only contain alphanumeric characters and underscores.');
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
        $moduleNamespace = count($nameParts) > 1 ? implode('\\', array_slice($nameParts, 0, -1)) : '';

        $moduleNamespaceSegment = $moduleNamespace ? '\\' . $moduleNamespace : '';
        $moduleNamespaceDir = $moduleNamespace ? '/' . str_replace('\\', '/', $moduleNamespace) : '';

        return [
            'moduleName' => Str::singular(Str::studly($actualModuleName)),
            'moduleNameLower' => Str::camel(Str::singular($actualModuleName)),
            'moduleNamePlural' => Str::plural(Str::studly($actualModuleName)),
            'moduleNamePluralLower' => Str::snake(Str::plural($actualModuleName)),
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
            $stubName = $config['stub'];
            $publishedStubFile = "{$publishedStubPath}/{$stubName}.stub";
            $packageStubFile = __DIR__ . "/../../../stubs/{$stubName}.stub";

            $this->stubPaths[$stubName] = File::exists($publishedStubFile)
                ? $publishedStubFile
                : $packageStubFile;
        }
    }

    private function createModuleComponents(array $namingFormats): bool
    {
        $success = true;
        $progressBar = $this->output->createProgressBar(count(self::MODULE_COMPONENTS));
        $progressBar->start();

        foreach (self::MODULE_COMPONENTS as $componentName => $config) {
            // Skip component if condition is met
            if (isset($config['skip_condition']) && $this->option($config['skip_condition'])) {
                $progressBar->advance();
                continue;
            }

            if (!$this->createModuleComponent($config, $namingFormats, $componentName)) {
                $success = false;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        return $success;
    }

    private function createModuleComponent(array $config, array $namingFormats, string $componentName): bool
    {
        $dirPath = $this->resolvePath($config['path'], $namingFormats);
        $className = $this->resolveClassName($config['class'], $namingFormats);
        $filePath = "{$dirPath}/{$className}.php";

        // Special handling for migration files
        if ($componentName === 'migration') {
            return $this->handleMigrationCreation($config, $namingFormats, $className, $dirPath);
        }

        if (File::exists($filePath) && !$this->shouldOverwrite($className)) {
            return true;
        }

        $this->ensureDirectoryExists($dirPath);

        if ($this->createFileFromStub($config['stub'], $filePath, $namingFormats, $className)) {
            $this->createdFiles[] = str_replace(base_path() . '/', '', $filePath);
            return true;
        }

        return false;
    }

    private function handleMigrationCreation(array $config, array $namingFormats, string $className, string $dirPath): bool
    {
        $migrationPattern = "*_create_{$namingFormats['moduleNamePluralLower']}_table.php";
        $existingMigrations = File::glob($dirPath . '/' . $migrationPattern);

        if (!empty($existingMigrations)) {
            $existingMigrationFile = basename($existingMigrations[0]);

            if (!$this->shouldOverwrite("migration for {$namingFormats['moduleNamePluralLower']} table")) {
                return true;
            }

            File::delete($existingMigrations[0]);
        }

        $filePath = "{$dirPath}/{$className}.php";
        $this->ensureDirectoryExists($dirPath);

        if ($this->createFileFromStub($config['stub'], $filePath, $namingFormats, $className)) {
            $this->createdFiles[] = str_replace(base_path() . '/', '', $filePath);
            return true;
        }

        return false;
    }

    private function shouldOverwrite(string $fileName): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm("The {$fileName} already exists. Do you want to replace it?");
    }

    private function resolveClassName(string $classTemplate, array $namingFormats): string
    {
        return str_replace(
            ['{moduleName}', '{moduleNamePluralLower}', '{timestamp}'],
            [
                $namingFormats['moduleName'],
                $namingFormats['moduleNamePluralLower'],
                $namingFormats['timestamp'],
            ],
            $classTemplate,
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

    private function createFileFromStub(string $stubName, string $targetPath, array $replacements, string $fileName): bool
    {
        try {
            $stubPath = $this->stubPaths[$stubName] ?? '';

            if (!File::exists($stubPath)) {
                $this->error("❌ Stub file not found: {$stubPath}");
                return false;
            }

            $stub = File::get($stubPath);
            $processedContent = $this->processStubContent($stub, $replacements);

            File::makeDirectory(dirname($targetPath), 0755, true, true);
            File::put($targetPath, $processedContent);

            return true;
        } catch (Exception $e) {
            $this->error("❌ Failed to create {$fileName}: {$e->getMessage()}");
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
