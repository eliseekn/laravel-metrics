# LaravelMetrics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)
[![Total Downloads](https://img.shields.io/packagist/dt/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)

Generate metrics and trends data from your database.

## Installation
```bash
composer require eliseekn/laravel-metrics
```

## Usage
```php
<?php

namespace App\Http\DashboardController;

use Eliseekn\LaravelMetrics\LaravelMetrics;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;

Class DashboardController extends Controller
{
    public function index(Request $request)
    {
        //generate trends data for your chart component
        $ordersTrends = LaravelMetrics::query(Order::query())
            ->sum('amount')
            ->byMonth(12)
            ->trends();
        
        $productsTrends = LaravelMetrics::query(Product::query())
            ->count()
            ->byYear(3)
            ->trends();

        //generate metrics data
        $totalOrders = LaravelMetrics::query(Order::query())
            ->sum('amount')
            ->byYear(1)
            ->metrics();

        //generate metrics data for a custum perod
        $totalUsers = LaravelMetrics::getMetrics('users', 'id', ['2021-01-01', '2021-12-31'], LaravelMetrics::MAX);

        return view('dashboard', compact('expensesTrends', 'userTrends', 'totalExpenses', 'totalUsers'));
    }
}
```

### Different types of periods
```php
LaravelMetrics::TODAY
LaravelMetrics::DAY
LaravelMetrics::WEEK
LaravelMetrics::MONTH
LaravelMetrics::YEAR
LaravelMetrics::QUATER_YEAR
LaravelMetrics::HALF_YEAR
```

### Different types of data
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
