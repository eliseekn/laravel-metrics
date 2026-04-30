# Metrics for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)
[![Total Downloads](https://img.shields.io/packagist/dt/eliseekn/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/eliseekn/laravel-metrics)

Generate easily metrics and trends data of your models for your dashboards.

## Requirements

```bash
PHP ^8.2.x
Laravel ^11.x
```

## Installation

```bash
composer require eliseekn/laravel-metrics
```

## Features

- MySQL, PostgreSQL and SQLite support
- Verbose query builder
- Custom columns and table definition
- Days and months translation with Carbon

## Usage

### With Eloquent Query

Import the `Eliseekn\LaravelMetrics\LaravelMetrics` class in your controller and use it as follows :

- Basic usage

```php
// generate trends of products amount's sum for the current year
LaravelMetrics::query(Product::query())
    ->count()
    ->byMonth()
    ->trends();

// generate trends of orders amount's sum for the last 6 months of the current year including current month
LaravelMetrics::query(Order::query())
    ->sum('amount')
    ->byMonth(6)
    ->trends();

// generate total orders amount's sum
LaravelMetrics::query(Order::query())
    ->sum('amount')
    ->byYear()
    ->metrics();

// generate total product count for the current day
LaravelMetrics::query(Product::query())
    ->count()
    ->byDay(1)
    ->metrics();
```

- Using custom query

```php
LaravelMetrics::query(
    Post::query()->where('user_id', auth()->id())
)
    ->count()
    ->byDay()
    ->trends();
```

- Using custom date column

```php
LaravelMetrics::query(Post::query())
    ->count()
    ->byDay()
    ->dateColumn('published_at')
    ->trends();
```

- Using date range

```php
LaravelMetrics::query(Post::query()))
    ->count()
    ->between('2020-05-01', '2022-08-21')
    ->trends();
```

- Using custom label column

```php
LaravelMetrics::query(Order::query())
    ->count()
    ->byMonth(12)
    ->labelColumn('status')
    ->trends();
```

- Using custom table

```php
LaravelMetrics::query(
    Order::query()->join('users', 'orders.id', 'users.order_id')
)
    ->count()
    ->table('users')
    ->labelColumn('name')
    ->trends();
```

### With Query Builder

```php
LaravelMetrics::query(
    DB::table('orders')
)
    ->sum('amount')
    ->byMonth()
    ->trends();
```

### With traits

Add `HasMetrics` trait to your models and use it as follows :

```php
Order::metrics()
    ->sum('amount')
    ->byMonth()
    ->trends();
```

### Types of periods

```php
LaravelMetrics::query(...)
    ->byDay(int $count = 0) //or
    ->byWeek(int $count = 0) //or
    ->byMonth(int $count = 0) //or
    ->byYear(int $count = 0) //or
    ->between(string $startDate, string $endDate, string $dateIsoFormat) //or
    ->from(string $date, string $dateIsoFormat)
```

**_Note :_** Periods are defined for the current day, week, month or year by default. However, you can define a specific value using dedicated methods. For example:

```php
// generate trends of orders count for the current year
LaravelMetrics::query(Order::query())
    ->count()
    ->byMonth(12)
    ->forYear(now()->year)
    ->labelColumn('status')
    ->trends();

// generate total orders amount's sum for the third month only
LaravelMetrics::query(Product::query())
    ->sum('amount')
    ->byMonth(1)
    ->forMonth(3)
    ->metrics();
```

```php
LaravelMetrics::query(...)
    ->forDay(int $day)
    ->forWeek(int $week)
    ->forMonth(int $month)
    ->forYear(int $year)
```

### Types of aggregates

```php
LaravelMetrics::query(...)
    ->count(string $column = 'id') //or
    ->average(string $column) //or
    ->sum(string $column) //or
    ->max(string $column) //or
    ->min(string $column)
```

### Types of data

```php
LaravelMetrics::query(...)
    ->trends(bool $inPercent = false) //or
    ->metrics() //or
    ->metricsWithVariations(int $previousCount, string $previousPeriod, bool $inPercent = false)
```

**_Note 1 :_** The `trends` method can generate data in percentage format when the `$inPercent` parameter is set to `true`.

**_Note 2 :_** The `metricsWithVariations` method generates metrics with variations from the `$previousPeriod` period (`day`, `week`, `month`, or `year`). The `$previousCount` parameter specifies the count for the past period. Set `$inPercent` parameter to true to get variations result in percent.

### Combining periods and aggregates

Combining different time periods and data aggregates can enhance your overall experience. For example :

```php
LaravelMetrics::query(...)
    ->sumByYear()
    ->trends();

LaravelMetrics::query(...)
    ->countByMonth(count: 12)
    ->forYear(now()->year)
    ->labelColumn('status')
    ->trends();

LaravelMetrics::query(...)
    ->countBetween([Carbon::now()->subDays(10)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
    ->trends();

LaravelMetrics::query(...)
    ->averageFrom(Carbon::now()->subDays(10)->format('Y-m-d'))
    ->trends();
...
```

Possible combinations :

```php
LaravelMetrics::query(...)
    ->countByMonth(...) //or
    ->countByYear(...) //or
    ->countByDay(...) //or
    ->countByWeek(...) //or
    ->sumByMonth(...) //or
    ->sumByYear(...) //or
    ->sumByDay(...) //or
    ->sumByWeek(...) //or
    ->averageByMonth(...) //or
    ->averageByYear(...) //or
    ->averageByDay(...) //or
    ->averageByWeek(...) //or
    ->maxByMonth(...) //or
    ->maxByYear(...) //or
    ->maxByDay(...) //or
    ->maxByWeek(...) //or
    ->minByMonth(...) //or
    ->minByYear(...) //or
    ->minByDay(...) //or
    ->minByWeek(...) //or
    ->countBetween(...) //or
    ->sumBetween(...) //or
    ->averageBetween(...) //or
    ->maxBetween(...) //or
    ->minBetween(...) //or
    ->countFrom(...) //or
    ->sumFrom(...) //or
    ->averageFrom(...) //or
    ->maxFrom(...) //or
    ->minFrom(...)
```

### Fill missing data with default value

You can fill missing data with default value with the global method `fillMissingData`, especially for trends. For example :

```php
LaravelMetrics::query(...)
    ->countBetween([Carbon::now()->subDays(10)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
    ->fillMissingData()
    ->trends();

LaravelMetrics::query(...)
    ->sumByYear(count: 5)
    ->fillMissingData()
    ->trends();
...
```

**_Note :_** The `fillMissingData` method automatically discovers all labels, ensuring that data is filled for all available labels without the need for explicit label specification.

### Group period (only when using `between` method)

You can group period by days, months, weeks or years when using the `between` method (**_default is day_**). For example :

```php
LaravelMetrics::query(...)
    ->countBetween([Carbon::now()->subDays(10)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
    ->groupByMonth()
    ->fillMissingData()
    ->trends();
```

```php
LaravelMetrics::query(...)
    ->groupByYear() //or
    ->groupByMonth() //or
    ->groupByWeek() //or
    ->groupByDay()
```

### Group data (only for `trends`)

You can group data of a column with multiple values to use it in a dataset for your charts. For example :

```php
Order::metrics()
    ->countByMonth(column: 'status')
    ->groupData(['pending', 'delivered', 'cancelled'], Aggregate::SUM->value)
    ->fillMissingData()
    ->trends();
```

**_Note :_** Follow same order in the example to avoid false data.

## Translations

Days and months names are automatically translated using `config(app.locale)` except 'week' period.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email `eliseekn@gmail.com` instead of using the issue tracker.

## Credits

- [N'Guessan Kouadio Elisée](https://github.com/eliseekn)
- [Chris Brown](https://github.com/drbyte)
- [Davey Shafik](https://github.com/dshafik)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Demo

You can find a demo project [here](https://github.com/eliseekn/laravel-metrics-demo).

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
