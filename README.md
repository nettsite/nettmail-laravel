# NettMail Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nettsite/nettmail-laravel.svg?style=flat-square)](https://packagist.org/packages/nettsite/nettmail-laravel)
[![GitHub Tests Action Status](https://github.com/nettsite/nettmail-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/nettsite/nettmail-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/nettsite/nettmail-laravel/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/nettsite/nettmail-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nettsite/nettmail-laravel.svg?style=flat-square)](https://packagist.org/packages/nettsite/nettmail-laravel)

Laravel adapter for [`nettmail/core`](https://github.com/nettsite/nettmail-core) — Eloquent models, service provider, queued jobs, and Livewire admin UI for the NettMail email package.

## Installation

You can install the package via composer:

```bash
composer require nettsite/nettmail-laravel
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="nettmail-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="nettmail-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="nettmail-views"
```

## Usage

```php
use NettSite\NettMail\Facades\NettMail;

NettMail::eraseContact($contactId);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nettsite](https://github.com/nettsite)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
