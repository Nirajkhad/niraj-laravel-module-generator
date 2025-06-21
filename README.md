
# Laravel NetSuite Integration

A comprehensive package that simplifies NetSuite integration with Laravel applications by automating the creation of controllers, services, actions, DTOs, and other components needed for effective data synchronization.

## Table of Contents
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Available Commands](#available-commands)

## Installation

### Add Repository

First, add the GitHub repository to your `composer.json`:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/intujicoder/niraj-laravel-netsuite"
  }
],
```

### Authenticate with GitHub

Add the token to Composer globally

```bash
composer config --global github-oauth.github.com your_github_token_here

```

### Install Package

Install the package via Composer:

```bash
composer require intujicoder/laravel-netsuite
```

### Publish Configuration and Stubs

After installation, publish the configuration files and stubs:

```bash
php artisan vendor:publish --provider="Intujicoder\LaravelNetsuite\ModuleServiceProvider"
```

This will create:
- Configuration files: `config/syncing-module.php` and `config/netsuite.php`
- Stub templates: `stubs/vendor/syncing-module`

## Configuration

### Syncing Module Configuration

The `config/syncing-module.php` file defines namespaces and paths for generated components:

```php
return [
    'base_namespace' => 'App',
    'netsuite' => [
        'namespace' => 'Netsuite',
    ],
    'paths' => [
        'controllers' => 'Http/Controllers',
        'services' => 'Services/Netsuite',
        'actions' => 'Actions/Netsuite',
        'dtos' => 'Dtos/Netsuite',
        'requests' => 'Http/Requests/Netsuite',
    ],
];
```

### NetSuite API Configuration

Configure your NetSuite API credentials in `config/netsuite.php`:

```php
return [
    'consumer_key' => env('NS_CONSUMER_KEY', ''),
    'consumer_secret' => env('NS_CONSUMER_SECRET', ''),
    'token_id' => env('NS_TOKEN_ID', ''),
    'token_secret' => env('NS_TOKEN_SECRET', ''),
    'script_id' => env('NS_SCRIPT_ID', ''),
    'deploy_id' => env('NS_DEPLOY_ID', ''),
    'account' => env('NS_ACCOUNT', ''),
    'oauth_sig_method' => env('NS_OAUTH_SIGN_METHOD', ''),
    'oauth_version' => env('NS_OAUTH_VERSION', ''),
    'url' => env('NS_URL', ''),
    'netsuite-token' => env('NS_WEB_TOKEN', ''),
    'endpoints' => [
        'post' => [
            'sync' => [
                'test' => 'createTest',
                // Add additional endpoints here
            ],
        ],
    ],
];
```

Be sure to add the corresponding environment variables to your `.env` file.

## Usage

### Generate a Module

Create a complete NetSuite integration module for any entity:

```bash
php artisan nestuite:make-module Customer
```

This command generates:
```
├── Http/Controllers/Netsuite/CustomerController.php
├── Services/Netsuite/CustomerService.php
├── Actions/Netsuite/Customer/StoreAction.php
├── Dtos/Netsuite/CustomerDto.php
├── Http/Requests/Netsuite/Customer/StoreRequest.php
└── ... additional files
```

### Generate a NetSuite Sync Job

Create a dedicated background job for NetSuite synchronization:

```bash
php artisan netsuite:make-job Customer
```

This creates:
```
└── Jobs/Netsuite/CustomerSyncJob.php
```

## Available Commands

| Command | Description | Parameters |
|---------|-------------|------------|
| `nestuite:make-module` | Creates a complete module with all components | `{moduleName}` - Name of entity (e.g., Customer) |
| `netsuite:make-job` | Generates a queue-ready sync job | `{module}` - Module to create job for |

Usage examples:
```bash
php artisan nestuite:make-module {moduleName}
php artisan netsuite:make-job {module}
```

## Architecture Overview

This package implements a robust architecture:

- **Controllers**: Handle HTTP requests/responses
- **Services**: Coordinate business logic and NetSuite interactions
- **Actions**: Single-responsibility classes for specific operations
- **DTOs**: Clean data transfer objects for transformation
- **Jobs**: Background processing for NetSuite synchronization

## Troubleshooting

Common issues:
- **Authentication failures**: Verify credentials in your `.env` file
- **Generation errors**: Check directory permissions and namespace configurations

For major changes, please open an issue first to discuss what you would like to change.
