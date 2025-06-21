
# Laravel Module Generator

[![Latest Version](https://img.shields.io/packagist/v/niraj/laravel-module-generator.svg?style=flat-square)](https://packagist.org/packages/niraj/laravel-module-generator)  
[![License](https://img.shields.io/packagist/l/niraj/laravel-module-generator.svg?style=flat-square)](LICENSE)  

A Laravel package that quickly generates complete CRUD modules including Controllers, Services, Actions, DTOs, Form Requests, Models, Migrations, and Resources via a single Artisan command.

---

## Why Use This Package?

Creating CRUD modules repeatedly can be tedious. This package scaffolds all the essential files you need to get started with clean, maintainable, and consistent code architecture — so you can focus on business logic instead of boilerplate.

---

## Features

- Generate Controllers, Services, and Action classes for CRUD operations  
- Generate Data Transfer Objects (DTOs) for data handling  
- Generate Form Request classes with validation  
- Generate Eloquent Models with UUID support  
- Generate timestamped database migration files  
- Generate API Resource classes for consistent JSON responses  
- Support for nested modules with proper namespaces  
- Configurable base namespace and paths  
- Stub files can be published and customized  

---

## Installation

Require the package via Composer:

```bash
composer require niraj/laravel-module-generator --dev
```

Publish configuration and stubs:

```bash
php artisan vendor:publish --tag=module-generation-config
php artisan vendor:publish --tag=module-generator-stubs
```

---

## Usage

Generate a new module with:

```bash
php artisan module:make {ModuleName}
```

Example:

```bash
php artisan module:make Customer
```

This command creates:

- Controller (`CustomerController.php`)  
- Service (`CustomerService.php`)  
- Actions: Index, Store, Update, Delete  
- DTO (`CustomerDto.php`)  
- Requests: Index, Store, Update  
- Model (`Customer.php`)  
- Migration file for the table  
- API Resource (`CustomerResource.php`)  

### Nested Modules

You can specify nested namespaces by using slashes:

```bash
php artisan module:make Admin/Customer
```

This generates the module under `App\Http\Controllers\Admin`, `App\Services\Admin`, etc.

---

## Configuration

Modify the published config file `config/module-generation-module.php` to customize:

- Base namespace  
- Paths for controllers, services, actions, DTOs, and requests  

---

## Customizing Stubs

Customize generated files by modifying the stub files located at:

```
stubs/vendor/module-generator-stubs
```

Publish stubs to your project by running the vendor publish command (shown above).

---

## Contributing

Feel free to open issues or submit pull requests on the [GitHub repo](https://github.com/nirajkhadka/laravel-module-generator).

---

## License

MIT License © Niraj Khadka

---

## Author

Niraj Khadka  
Email: khadka.niraj11111@gmail.com  
GitHub: [nirajkhadka](https://github.com/nirajkhadka)

---


### Usage Examples

#### Basic Module Generation

Generate a simple module named Product:

```bash
php artisan module:make Product
```

This creates:

- `app/Http/Controllers/ProductController.php`
- `app/Services/ProductService.php`
- Actions: `IndexAction.php`, `StoreAction.php`, `UpdateAction.php`, `DeleteAction.php`
- DTO: `ProductDto.php`
- Requests: `IndexRequest.php`, `StoreRequest.php`, `UpdateRequest.php`
- Model: `Product.php`
- Migration: timestamped `create_products_table.php`
- API Resource: `ProductResource.php`

#### Nested Module with Namespace

Generate a nested module `Admin/User` with namespacing:

```bash
php artisan module:make Admin/User
```

Creates:

- `app/Http/Controllers/Admin/UserController.php`
- `app/Services/Admin/UserService.php`
- Actions inside `app/Actions/Admin/User/`
- Requests inside `app/Http/Requests/Admin/User/`
- Model: `app/Models/Admin/User.php` (if your config supports nested models)
- Migration and resources properly namespaced

This keeps code organized in subfolders and namespaces.

#### Customizing Namespace and Paths

Publish the config file:

```bash
php artisan vendor:publish --tag=module-generation-config
```

Then edit `config/module-generation-module.php`:

```php
return [
    'base_namespace' => 'App',
    'paths' => [
        'controllers' => 'Http/Controllers/Custom',
        'services' => 'Domain/Services',
        'actions' => 'Domain/Actions',
        'dtos' => 'Domain/Dtos',
        'requests' => 'Http/Requests/Custom',
    ],
];
```

Now when you run `module:make Product`, files will generate inside your custom directories.

---

### FAQ

**Q: Can I generate only specific parts of the module?**  
A: Currently, the package generates the full CRUD module at once. Selective generation may be planned for future versions.

**Q: How do I override stub templates?**  
A: Run:

```bash
php artisan vendor:publish --tag=module-generator-stubs
```

This publishes stub files to `stubs/vendor/module-generator-stubs/`. Modify these `.stub` files to customize generated code templates.

**Q: Will this work with Laravel versions below 9?**  
A: No, it requires Laravel 9 or higher due to dependencies and PHP 8.1+ features.

**Q: How do I change UUID generation or disable it?**  
A: Modify the model stub in your published stubs directory. You can change or remove the `HasUuids` trait and UUID logic as needed.

**Q: How to add additional fields to the migration?**  
A: Edit the migration stub after publishing, or manually add columns after generation.

---

### Troubleshooting Tips

- **Command Not Found:** Ensure package is installed via Composer and `ModuleServiceProvider` is registered (auto-discovered by default).

- **Stubs Not Publishing:** Check write permissions on your `stubs/` directory and run the publish command again.

- **Namespace Issues:** Verify the `base_namespace` and paths in the config file match your Laravel app structure.

- **Migration Timestamp Conflicts:** If migration with the same name exists, you will be prompted to overwrite or skip.

- **Model Not Found in Controller:** Check your namespace configurations, especially if you use nested modules.

---

### Code Snippets for Extending or Modifying Generated Modules

#### Adding a New Method to the Service

Open the generated service, e.g., `app/Services/ProductService.php` and add:

```php
public function getByName(string $name): ?Product
{
    return Product::where('name', $name)->first();
}
```

#### Adding Custom Validation to Requests

Modify `StoreRequest.php`:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'min:3', 'unique:products,name'],
        'price' => ['required', 'numeric', 'min:0'],
    ];
}
```

Add new fields accordingly in DTO and migration stubs as well.

#### Extending Controller with a Custom Endpoint

In `ProductController.php`, add:

```php
public function search(Request $request)
{
    $name = $request->input('name');

    $product = $this->productService->getByName($name);

    if (!$product) {
        return response()->json(['message' => 'Product not found'], 404);
    }

    return response()->json(['data' => ProductResource::make($product)]);
}
```

Add a route to `routes/api.php`:

```php
Route::get('products/search', [ProductController::class, 'search']);
```

---

### Customizing Stub Files

After publishing stubs, edit any `.stub` file inside `stubs/vendor/module-generator-stubs/` such as `controller.stub`:

Replace placeholders or add custom traits, imports, or methods that fit your coding style or project standards.