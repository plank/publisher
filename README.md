<p align="center"><a href="https://plank.co"><img src="art/publisher.png" width="100%"></a></p>

<p align="center">
<a href="https://packagist.org/packages/plank/publisher"><img src="https://img.shields.io/packagist/php-v/plank/publisher?color=%23fae370&label=php&logo=php&logoColor=%23fff" alt="PHP Version Support"></a>
<a href="https://github.com/plank/publisher/actions?query=workflow%3Arun-tests"><img src="https://img.shields.io/github/actions/workflow/status/plank/publisher/run-tests.yml?branch=main&&color=%23bfc9bd&label=run-tests&logo=github&logoColor=%23fff" alt="GitHub Workflow Status"></a>
<a href="https://codeclimate.com/github/plank/publisher/test_coverage"><img src="https://img.shields.io/codeclimate/coverage/plank/publisher?color=%23ff9376&label=test%20coverage&logo=code-climate&logoColor=%23fff" /></a>
<a href="https://codeclimate.com/github/plank/publisher/maintainability"><img src="https://img.shields.io/codeclimate/maintainability/plank/publisher?color=%23528cff&label=maintainablility&logo=code-climate&logoColor=%23fff" /></a>
</p>

# Laravel Publisher

:warning: Package is under active development. Wait for v1.0.0 for production use. :warning:

Publisher is a package aimed at providing a simple and flexible way to manage the publishing workflow of content in a Laravel application. It is designed to be used with any type of content, such as blog posts, pages, or any other type of content that may require a publishing workflow.

A key requirement Publisher aims to solve, is the ability to work on existing content without the changes being visible to your site's regular users until the changes are ready to be published, without the existing published version going missing from your site.

In Version 2+, Publisher will also provide the ability to manage relationships to content that is in a draft state without that related draft content being visible to your site's regular users.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
  - [Migration Columns](#migration-columns)
  - [Middleware](#middleware)
  - [URL Rewriting](#url-rewriting)
  - [Admin Panel](#admin-panel)
  - [Gates & Abilities](#gates--abilities)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)
- [Security Vulnerabilities](#security-vulnerabilities)

&nbsp;

## Installation

1. You can install the package via composer:

    ```bash
    composer require plank/publisher
    ```

2. Add the `Plank\Publisher\Concerns\IsPublishable` trait and the `Plank\Publisher\Contracts\Publishable` interface to the models you want to respect the publishing workflow.

3. Use the package's install command to complete the installation:

    ```bash
    php artisan publisher:install
    ```

&nbsp;

## Configuration

The package's configuration file is located at `config/publisher.php`. If you did not publish the config file during installation, you can publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="Plank\Publisher\PublisherServiceProvider" --tag="config"
```

### Migration Columns

Each Publishable Model requires 3 columns on their table to function properly:

1. `workflow`
   - The workflow columns stores the current state of the model. Whether is is currently published, or being worked on as a draft.
2. `draft`
   - The draft column stores the working values of the attributes in a json column
3. `has_been_published`
   - The has_been_published column stores a boolean value to indicate if the model has ever been published.

As a further note, you could in theory extend the values of the `workflow` column to include more states, such as "in_review". However, there are two important states that are required for the package to function properly: a "published" state, and an "unpublished" state. These states are configurable in the `IsPublishable` trait, by overriding the `publishedState` and `unpublishedState` methods.

Whenever a model is transitioned to the "published" state, the `has_been_published` column is set to `true`, and the model is considered to have been published.

Whenever a model is transitioned out of the "published" state, changes to the model's attributes will be persisted to the `draft` column, and the `workflow` column will be set to the "unpublished" state.

&nbsp;

### Middleware

The package provides a middleware that can be enabled to toggle the visiblity of draft content in the application. This is useful for allowing specific users to preview draft content in a production environment.

You can disable the packages middleware and create your own by setting the `middleware.enabled` key to `false` in the configuration file.

If you are using the package's middleware, you can choose if you would like it to be enabled as global middleware or route middleware by setting the `middleware.global` key to `true` or `false` respectively.

&nbsp;

### URL Rewriting

This package provides an opt-out feature which overrides all URL generation done by the frameworks methods like `url()` and `route()` to preserve the current visiblity of the draft content.

You can disable the feature by setting the `urls.rewrite` key to `false` in the configuration file.

You can also configure the GET query parameter used to signify the site should display draft content by setting the `urls.previewKey` key to the desired value.

When the value configured in `previewKey` is present in the GET query, AND the user has the `Gate` ability to `view-draft-content`, the package will allow draft content and rewrite all urls to include the `previewKey`.

&nbsp;

### Admin Panel

It is assumed that your Admin panel should always allow draft content to be visible. If you are using the package's middleware, you can specify the route prefix of your admin panel by setting the `admin.path` key in the configuration file.

When this key is set all routes that start with the specified prefix will always have draft content enabled.

&nbsp;

### Gates & Abilities

All gates defined on the package can be overriden in your app by defining the gate with the same name in an Application Service Provider.

The package implementation of all gates is as follows:

```php
Gate::define('publish', function ($user, $model) {
    return $user !== null;
});
```

#### publish

This gate is used to determine if a user has the ability to publish a model. By default, the gate is defined as follows:

#### unpublish

This gate is used to determine if a user has the ability to unpublish a model. By default, the gate is defined as follows:

#### view-draft-content

This gate is used to determine if a user has the ability to view draft content. By default, the gate is defined as follows:

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

&nbsp;

## Credits

- [Kurt Friars](https://github.com/kfriars)
- [Massimo Triassi](https://github.com/m-triassi)
- [Andrew Hanichkovsky](https://github.com/a-drew)
- [All Contributors](../../contributors)

&nbsp;

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

&nbsp;

## Security Vulnerabilities

If you discover a security vulnerability within siren, please send an e-mail to [security@plank.co](mailto:security@plank.co). All security vulnerabilities will be promptly addressed.

&nbsp;

## Check Us Out!

<a href="https://plank.co/open-source/learn-more-image">
    <img src="https://plank.co/open-source/banner">
</a>

&nbsp;

Plank focuses on impactful solutions that deliver engaging experiences to our clients and their users. We're committed to innovation, inclusivity, and sustainability in the digital space. [Learn more](https://plank.co/open-source/learn-more-link) about our mission to improve the web.
