# Metrics for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)
[![Total Downloads](https://img.shields.io/packagist/dt/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)

Generate easily metrics and trends data of your models for your dashboards.

## Installation
```bash
composer require eliseekn/laravel-metrics
```

## Features
- MySQL support
- Verbose query builder
- Custom columns definition support
- Days and months names translation with Carbon

## Usage
Import the `Eliseekn\LaravelMetrics\LaravelMetrics` class in your controller and use it as follows :

```php
// generate trends of the sum of the orders amount for the current year
LaravelMetrics::query(Order::query())
    ->sum('amount')
    ->byMonth()
    ->trends();

// generate trends of the sum of the orders amount for the last 6 months of the current year including the current month
LaravelMetrics::query(Order::query())
    ->sum('amount')
    ->byMonth(6)
    ->trends();

// generate trends of count of the products for the last 3 years including the current year
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
    
// generate total count of the product for the current month
LaravelMetrics::query(Product::query())
    ->count()
    ->byWeek()
    ->metrics();
    
// generate trends of count of posts for the current month
// by using a custom query and a custom date column
LaravelMetrics::query(
    Post::query()->where('user_id', auth()->id())
)
    ->count()
    ->byDay()
    ->dateColumn('published_at')
    ->trends();
    
// generate trends of count of posts for a range of dates
LaravelMetrics::query(
    Post::query()->where('user_id', auth()->id())
)
    ->count()
    ->between('2020-05-01', '2022-08-21')
    ->trends();

// generate total count of the orders for the current year
// by using a custom label column
LaravelMetrics::query(Order::query())
    ->count()
    ->byMonth(12)
    ->labelColumn('status')
    ->trends();
```

### Types of periods
```php
->byDay(int $count = 0)
->byWeek(int $count = 0)
->byMonth(int $count = 0)
->byYear(int $count = 0)
->between(string $startDate, string $endDate)
```

```php
$count = 0 => for every day, week, month or year 
$count = 1 => for the current day, week, month or year
$count > 1 => for an interval of day, week, month or year from the $count value to now
$period = 'day', 'week', 'month' or 'year'
```

#### Notes
Periods are typically defined for the current day, week, month or year. However, you can define a specific value using dedicated methods. For example:
```php
// generate total count of the orders for the year 2023
//// by using a custom label column
LaravelMetrics::query(Order::query())
    ->count()
    ->byMonth(12)
    ->forYear(2023)
    ->labelColumn('status')
    ->trends();

// generate total count of the product for the current day of the month february
LaravelMetrics::query(Product::query())
    ->count()
    ->byDay(1)
    ->forMonth(2)
    ->metrics();

// generate total sum of the orders amount for the month march only
LaravelMetrics::query(Product::query())
    ->sum('amount')
    ->byMonth(1)
    ->forMonth(3)
    ->metrics();
```

```php
->forDay(int $day)
->forWeek(int $week)
->forMonth(int $month)
->forYear(int $year)
```


### Types of aggregates
```php
->count(string $column = 'id')
->average(string $column)
->sum(string $column)
->max(string $column)
->min(string $column)
```

### Types of data
```php
->trends()  // retrieves trends values for charts
->metrics() // retrieves total values
```

### Traits

Add `HasMetrics` trait to your models and use it as follows :
```php
// generate trends of the sum of the orders amount for the current year
Order::metrics()
    ->sum('amount')
    ->byMonth()
    ->trends();
    
// generate total count of the product for the current month
Product::metrics()
    ->count()
    ->byWeek()
    ->metrics();
```
## Translations

Days and months names are automatically translated using `config(app.locale)` except 'week' period.

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
