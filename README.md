<p align="center"><a href="https://plank.co"><img src="art/publisher.png" width="100%"></a></p>

<p align="center">
<a href="https://packagist.org/packages/plank/publisher"><img src="https://img.shields.io/packagist/php-v/plank/publisher?color=%23fae370&label=php&logo=php&logoColor=%23fff" alt="PHP Version Support"></a>
<a href="https://github.com/plank/publisher/actions?query=workflow%3Arun-tests"><img src="https://img.shields.io/github/actions/workflow/status/plank/publisher/run-tests.yml?branch=main&&color=%23bfc9bd&label=run-tests&logo=github&logoColor=%23fff" alt="GitHub Workflow Status"></a>
<a href="https://codeclimate.com/github/plank/publisher/test_coverage"><img src="https://img.shields.io/codeclimate/coverage/plank/publisher?color=%23ff9376&label=test%20coverage&logo=code-climate&logoColor=%23fff" /></a>
<a href="https://codeclimate.com/github/plank/publisher/maintainability"><img src="https://img.shields.io/codeclimate/maintainability/plank/publisher?color=%23528cff&label=maintainablility&logo=code-climate&logoColor=%23fff" /></a>
</p>

# Laravel Publisher

:warning: Package is under active development. Wait for v1.0.0 for production use. :warning:

Publisher is...

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)
- [Security Vulnerabilities](#security-vulnerabilities)

&nbsp;

## Installation

You can install the package via composer:

```bash
composer require plank/publisher
```

You can use the package's install command to complete the installation:

```bash
php artisan publisher:install
```

## Quick Start

Once the installation has completed, to begin using the package:

1. Add the `Plank\Publisher\Concerns\IsPublishable` trait and the `Plank\Publisher\Contracts\Publishable` interface to the models you want to respect the publishing workflow.

2. Add the [publisher columns](#migrations) to the tables for those models.
3. Create middleware[s] which will determine when draft content should be visible.

```php
&nbsp;

## Configuration

The package's configuration file is located at `config/publisher.php`. If you did not publish the config file during installation, you can publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="Plank\Publisher\PublisherServiceProvider" --tag="config"
```

&nbsp;


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
