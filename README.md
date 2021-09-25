# LaravelMetrics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)
[![Total Downloads](https://img.shields.io/packagist/dt/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)

Generate metrics and trends data from your database.

## Installation

You can install the package via composer:

```bash
composer require eliseekn/laravel-metrics
```

## Usage

```php
$userTrends = LaravelMetrics::getTrends('users', 'id', LaravelMetrics::YEAR, LaravelMetrics::COUNT);
$userMetrics = LaravelMetrics::getMetrics('users', 'expenses', LaravelMetrics::TODAY, LaravelMetrics::SUM);
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email eliseekn@gmail.com instead of using the issue tracker.

## Credits

-   [N'Guessan Kouadio Elisée](https://github.com/eliseekn)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
