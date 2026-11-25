# Installation

This guide will walk you through installing and configuring Laravel Publisher in your application.

## Requirements

-   Laravel 12.x or 13.x
-   PHP 8.2+ for Laravel 12, or PHP 8.3+ for Laravel 13

Laravel 10 and 11 are no longer supported. See [Version Support](../../README.md#version-support).

## Installation Steps

### 1. Install via Composer

```bash
composer require plank/publisher
```

### 2. Add the Publishable Interface and Trait

Add the `Publishable` interface and `IsPublishable` trait to any models that require the publishing workflow:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Concerns\IsPublishable;
use Plank\Publisher\Contracts\Publishable;

class Post extends Model implements Publishable
{
    use IsPublishable;
}
```

### 3. Run the Install Command

The install command will guide you through setup, including publishing the configuration and generating migrations for your publishable models:

```bash
php artisan publisher:install
```

The installer will:

-   Confirm your models implement the `Publishable` interface
-   Optionally publish the configuration file
-   Optionally generate migrations for your publishable models

### 4. Add publisher columns to your model(s) if you did not generate them automatically

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends SnapshotMigration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('status');
            $table->boolean('has_been_published');
            $table->boolean('should_delete');
            $table->json('draft')->nullable();
        });
    }
}
```

### 5. Run Migrations

```bash
php artisan migrate
```

## Manual Migration Generation

If you need to generate migrations for additional models later, you can also use:

```bash
php artisan publisher:migrations
```

This creates migrations adding the following columns to your publishable models:

| Column               | Type      | Description                               |
| -------------------- | --------- | ----------------------------------------- |
| `draft`              | `json`    | Stores working draft attribute values     |
| `status`             | `string`  | Current workflow state (published/draft)  |
| `has_been_published` | `boolean` | Whether the model has ever been published |
| `should_delete`      | `boolean` | Whether the model is queued for deletion  |

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Plank\Publisher\PublisherServiceProvider" --tag="config"
```

The configuration file will be published to `config/publisher.php`:

```php
<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;
use Plank\Publisher\Listeners\HandleSchemaConflicts;

return [
    // The enum class used for workflow states
    'workflow' => Status::class,

    // Column name mappings
    'columns' => [
        'draft' => 'draft',
        'workflow' => 'status',
        'has_been_published' => 'has_been_published',
        'should_delete' => 'should_delete',
    ],

    // URL rewriting settings for preview mode
    'urls' => [
        'rewrite' => true,
        'previewKey' => 'preview',
    ],

    // Request paths where draft content is always enabled
    'draft_paths' => [
        'admin*',
        'nova-api*',
        'nova-vendor*',
    ],

    // Schema conflict resolution settings
    'conflicts' => [
        'queue' => 'sync',
        'listener' => HandleSchemaConflicts::class,
        'job' => ResolveSchemaConflicts::class,
    ],

    // Ignore settings
    'ignore' => [
        'counts' => true,
    ],
];
```

## Register Middleware

Add the Publisher middleware to your application. This should be placed **before** `SubstituteBindings` in your middleware stack.

### Laravel 13.x and 12.x

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(prepend: [
        \Plank\Publisher\Middleware\PublisherMiddleware::class,
    ]);
})
```

Or for specific route groups:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', [
        // ... other middleware
        \Plank\Publisher\Middleware\PublisherMiddleware::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ]);
})
```

### Applications that still use `app/Http/Kernel.php`

If your application was upgraded from an older Laravel version and still uses the HTTP kernel, add the middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Plank\Publisher\Middleware\PublisherMiddleware::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

## Next Steps

-   Read [Core Concepts](core-concepts.md) to understand the publishing workflow
-   Learn about [Draft Management](../features/draft-management.md)
-   Configure [Authorization](../features/authorization.md) gates
