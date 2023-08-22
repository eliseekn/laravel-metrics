# LaravelMetrics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)
[![Total Downloads](https://img.shields.io/packagist/dt/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)

Generate easily metrics and trends data of your models for your dashboards.

## Installation
```bash
composer require eliseekn/laravel-metrics
```

## Features
- MySQL and SQLite support
- Verbose query builder
- Custom date column definition
- No ambiguous column

## Usage
Import the `Eliseekn\LaravelMetrics\LaravelMetrics` class in your controller and use it as follows :

```php
// generate trends of the sum of the orders amount for the last 12 months of the current year
LaravelMetrics::query(Order::query())
    ->sum('amount')
    ->byMonth(12)
    ->trends();

// generate trends of count of the products for the last 3 years
LaravelMetrics::query(Product::query())
    ->count()
    ->byYear(3)
    ->trends();
            
// generate total sum of the orders amount for every year
LaravelMetrics::query(Order::query())
    ->sum('amount')
    ->byYear()
    ->metrics(); 

// generate total count of the product for the current day of the current month
LaravelMetrics::query(Product::query())
    ->count()
    ->byDay(1)
    ->metrics();
    
// generate trends of count of posts for the last 7 days of the current month
// by using a custom query and a specific date column (published_at)
LaravelMetrics::query(
    Post::query()->where('user_id', auth()->id())
)
    ->count()
    ->byDay(7)
    ->dateColumn('published_at')
    ->trends();
    
// generate trends of count of posts for a range of dates
// by using a custom query and a specific date column (published_at)
LaravelMetrics::query(
    Post::query()->where('user_id', auth()->id())
)
    ->count()
    ->between('2020-05-01', '2022-08-21')
    ->dateColumn('published_at')
    ->trends();
```

### Types of periods
```php
->byDay($count = 0)
->byMonth($count = 0)
->byYear($count = 0)
->by($period, $count = 0)
->between($startDate, $endDate)
```

```php
$count = 0 => for every day, month or year 
$count = 1 => for the current day, month or year
$count > 1 => for an interval of day, month or year from the $count value to now
$period = 'day', 'month' or 'year'
```

### Types of aggregates
```php
->count('column')
->average('column')
->sum('column')
->max('column')
->min('column')
```

### Types of data
```php
->trends()  // retrieves trends values for charts
->metrics() // retrieves total values
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email `eliseekn@gmail.com` instead of using the issue tracker.

## Credits

-   [N'Guessan Kouadio Elisée](https://github.com/eliseekn)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Demo

You can find a demo project [here](https://github.com/eliseekn/laravel-metrics-demo).

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
