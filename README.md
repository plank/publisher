<p align="center"><a href="https://plank.co"><img src="art/publisher.png" width="100%"></a></p>

<p align="center">
<a href="https://packagist.org/packages/plank/publisher"><img src="https://img.shields.io/packagist/php-v/plank/publisher?color=%23fae370&label=php&logo=php&logoColor=%23fff" alt="PHP Version Support"></a>
<a href="https://laravel.com/docs/13.x/releases#support-policy"><img src="https://img.shields.io/badge/laravel-12.x%20%7C%2013.x-%2343d399?color=%23f1ede9&logo=laravel&logoColor=%23ffffff" alt="Laravel Version Support"></a>
<a href="https://github.com/plank/publisher/actions?query=workflow%3Arun-tests"><img src="https://img.shields.io/github/actions/workflow/status/plank/publisher/run-tests.yml?branch=main&&color=%23bfc9bd&label=run-tests&logo=github&logoColor=%23fff" alt="GitHub Workflow Status"></a>
</p>

# Laravel Publisher

Publisher is a Laravel package that provides a complete content publishing workflow, allowing you to maintain both published and draft versions of your content simultaneously. Editors can work on changes without affecting the live published version until changes are explicitly published.

## Version Support

Each Publisher major version matches one Laravel major version.

| Laravel | PHP  | Publisher | Status                                |
| ------- | ---- | --------- | ------------------------------------- |
| 13.x    | 8.3+ | ^13.0     | Supported                             |
| 12.x    | 8.2+ | ^12.0     | Supported                             |
| 11.x    | 8.2+ | ^11.0     | No longer supported (last: v11.12.0)  |
| 10.x    | 8.2+ | ^10.0     | No longer supported (last: v10.12.0)  |

Laravel 10 and 11 are end-of-life. They have security advisories that will not be fixed, and Composer 2.11 and later blocks every 10.x and 11.x release of `laravel/framework` by default. The existing Publisher releases for these versions stay on Packagist, but they will not get more updates. To keep getting updates, upgrade to Laravel 12 or 13.

## Installation

1. Install the package via composer:

    ```bash
    composer require plank/publisher
    ```

2. Add the `Publishable` interface and `IsPublishable` trait to your models:

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

3. Run the install command:

    ```bash
    php artisan publisher:install
    ```

4. Add the publisher columns to your model if you did not generate them automatically:

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

5. Run migrations:

    ```bash
    php artisan migrate
    ```

## Documentation

For complete documentation including configuration, features, and advanced usage, see the [Documentation](docs/README.md).

**Quick Links:**

-   [Installation Guide](docs/guides/installation.md)
-   [Core Concepts](docs/guides/core-concepts.md)
-   [Publishable Relationships](docs/features/publishable-relationships.md)
-   [Admin Panel Integration](docs/advanced/admin-panel-integration.md)

&nbsp;

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

&nbsp;

## Credits

-   [Kurt Friars](https://github.com/kfriars)
-   [Massimo Triassi](https://github.com/m-triassi)
-   [All Contributors](../../contributors)

&nbsp;

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

&nbsp;

## Security Vulnerabilities

If you discover a security vulnerability within Publisher, please send an e-mail to [security@plank.co](mailto:security@plank.co). All security vulnerabilities will be promptly addressed.

&nbsp;

## Check Us Out!

<a href="https://plank.co/open-source/learn-more-image">
    <img src="https://plank.co/open-source/banner">
</a>

&nbsp;

Plank focuses on impactful solutions that deliver engaging experiences to our clients and their users. We're committed to innovation, inclusivity, and sustainability in the digital space. [Learn more](https://plank.co/open-source/learn-more-link) about our mission to improve the web.
