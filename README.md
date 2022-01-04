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
<?php

namespace App\Http\DashboardController;

use Eliseekn\LaravelMetrics\LaravelMetrics;
use Illuminate\Http\Request;

Class DashboardController extends Controller
{
    public function index(Request $request)
    {
        //generate trends data for your chart component
        $expensesTrends = LaravelMetrics::getTrends('expenses', 'amount', LaravelMetrics::YEAR, LaravelMetrics::SUM);
        $userTrends = LaravelMetrics::getTrends('users', 'id', LaravelMetrics::QUATER_YEAR, LaravelMetrics::COUNT);

        //generate metrics data
        $totalExpenses = LaravelMetrics::getMetrics('expenses', 'amount', LaravelMetrics::QUATER_YEAR, LaravelMetrics::SUM);

        //generate metrics data for a custum perod
        $totalUsers = LaravelMetrics::getMetrics('users', 'id', ['2021-01-01', '2021-12-31'], LaravelMetrics::MAX);

        return view('dashboard', compact('expensesTrends', 'userTrends', 'totalExpenses', 'totalUsers'));
    }
}
```

### Differents types of periods
```php
LaravelMetrics::TODAY
LaravelMetrics::DAY
LaravelMetrics::WEEK
LaravelMetrics::MONTH
LaravelMetrics::YEAR
LaravelMetrics::QUATER_YEAR
LaravelMetrics::HALF_YEAR
```

### Differents types of data
```php
LaravelMetrics::COUNT
LaravelMetrics::AVERAGE
LaravelMetrics::SUM
LaravelMetrics::MAX
LaravelMetrics::MIN
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

## Demo

You can find a demo project [here](https://github.com/eliseekn/laravel-metrics-demo).

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
