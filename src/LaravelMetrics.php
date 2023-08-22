<?php
declare(strict_types=1);

namespace Eliseekn\LaravelMetrics;

use Carbon\Carbon;
use DateTime;
use Eliseekn\LaravelMetrics\Exceptions\InvalidDateFormatException;
use Eliseekn\LaravelMetrics\Exceptions\InvalidPeriodException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * LaravelMetrics
 * 
 * Generate metrics and trends data from your database
 */
class LaravelMetrics
{
    protected const DAY = 'day';
    protected const MONTH = 'month';
    protected const YEAR = 'year';
    protected const COUNT = 'COUNT';
    protected const AVERAGE = 'AVG';
    protected const SUM = 'SUM';
    protected const MAX = 'MAX';
    protected const MIN = 'MIN';

    protected const DAY_NAME = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];

    protected const MONTH_NAME = [
        'December',
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
    ];

    protected string $table;
    protected string $column = 'id';
    protected string|array $period = self::MONTH;
    protected string $type = self::COUNT;
    protected string $dateColumn;
    protected int $count = 0;
    protected int $year;
    protected int $month;
    protected int $day;

    public function __construct(protected Builder $builder)
    {
        $this->table = $this->builder->from;
        $this->dateColumn = $this->table . '.created_at';
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->day = Carbon::now()->day;
    }

    public static function query(Builder $builder): self
    {
        return new self($builder);
    }

    public function byDay(int $count = 0): self
    {
        $this->period = self::DAY;
        $this->count = $count;
        return $this;
    }

    public function byMonth(int $count = 0): self
    {
        $this->period = self::MONTH;
        $this->count = $count;
        return $this;
    }

    public function byYear(int $count = 0): self
    {
        $this->period = self::YEAR;
        $this->count = $count;
        return $this;
    }

    public function by(string $period, int $count = 0): self
    {
        $period = strtolower($period);

        if (!in_array($period, [self::DAY, self::MONTH, self::YEAR])) {
            throw new InvalidPeriodException();
        }

        $this->period = $period;
        $this->count = $count;
        return $this;
    }

    public function between(string $start, string $end): self
    {
        $this->checkDateFormat([$start, $end]);
        $this->period = [$start, $end];
        return $this;
    }

    public function count(string $column = 'id'): self
    {
        $this->type = self::COUNT;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function average(string $column): self
    {
        $this->type = self::AVERAGE;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function sum(string $column): self
    {
        $this->type = self::SUM;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function max(string $column): self
    {
        $this->type = self::MAX;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function min(string $column): self
    {
        $this->type = self::MIN;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function dateColumn(string $column): self
    {
        $this->dateColumn = $this->table . '.' . $column;
        return $this;
    }

    protected function metricsData(): mixed
    {
        if (is_array($this->period)) {
            return $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->whereBetween(DB::raw("date($this->dateColumn)"), [$this->period[0], $this->period[1]])
                ->first();
        }

        return match ($this->period) {
            self::DAY => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count > 0, function (QueryBuilder $query) {
                    $column = self::driver() === 'sqlite'
                        ? "strftime('%d', $this->dateColumn)"
                        : "day($this->dateColumn)";

                    return $query->whereBetween(DB::raw($column), [
                        Carbon::now()->subDays($this->count)->day, $this->day
                    ]);
                })
                ->first(),

            self::MONTH => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->whereYear($this->dateColumn, $this->year)
                ->when($this->count > 0, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::MONTH)), [
                        Carbon::now()->subMonths($this->count)->month, $this->month
                    ]);
                })
                ->first(),

            self::YEAR => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->when($this->count > 0, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::YEAR)), [
                        Carbon::now()->subYears($this->count)->year, $this->year
                    ]);
                })
                ->first(),

            default => null,
        };
    }

    protected function trendsData(): Collection
    {
        if (is_array($this->period)) {
            return $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, date($this->dateColumn) as label")
                ->whereBetween(DB::raw('date(' . $this->dateColumn . ')'), [$this->period[0], $this->period[1]])
                ->groupBy('label')
                ->get();
        }

        return match ($this->period) {
            self::DAY => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::DAY) . " as label")
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count > 0, function (QueryBuilder $query) {
                    $column = self::driver() === 'sqlite'
                        ? "strftime('%d', ' . $this->dateColumn)"
                        : "day($this->dateColumn)";

                    return $query->whereBetween(DB::raw($column), [
                        Carbon::now()->subDays($this->count)->day, $this->day
                    ]);
                })
                ->groupBy('label')
                ->get(),

            self::MONTH => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::MONTH) . " as label")
                ->whereYear($this->dateColumn, $this->year)
                ->when($this->count > 0, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::MONTH)), [
                        Carbon::now()->subMonths($this->count)->month, $this->month
                    ]);
                })
                ->groupBy('label')
                ->get(),

            self::YEAR => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::YEAR) . " as label")
                ->when($this->count > 0, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::YEAR)), [
                        Carbon::now()->subYears($this->count)->year, $this->year
                    ]);
                })
                ->groupBy('label')
                ->get(),

            default => [],
        };
    }

    protected function driver(): string
    {
        $connection = Config::get('database.default');
        return Config::get("database.connections.$connection.driver");
    }

    protected function formatPeriod(string $period): string
    {
        return match ($period) {
            self::DAY => self::driver() === 'sqlite' ? "strftime('%w', $this->dateColumn)" : "weekday($this->dateColumn)",
            self::MONTH => self::driver() === 'sqlite' ? "strftime('%m', $this->dateColumn)" : "month($this->dateColumn)",
            self::YEAR => self::driver() === 'sqlite' ? "strftime('%Y', $this->dateColumn)" : "year($this->dateColumn)",
            default => '',
        };
    }

    protected function formatDate(array $data): array
    {
        return array_map(function ($data)  {
            if ($this->period === self::MONTH) {
                $data->label = self::MONTH_NAME[intval($data->label)];
            } else if ($this->period === self::DAY) {
                if (self::driver() === 'sqlite') {
                    $data->label = self::DAY_NAME[intval($data->label)];
                } else {
                    $data->label = self::DAY_NAME[intval($data->label) + 1] ?? self::DAY_NAME[0];
                }
            } else {
                $data->label = intval($data->label);
            }

            return $data;
        }, $data);
    }

    /**
     * Generate metrics data
     */
    public function metrics(): mixed
    {
        $metricsData = $this->metricsData();
        return is_null($metricsData) ? 0 : $metricsData->data;
    }

    /**
     * Generate trends data for charts
     */
    public function trends(): array
    {
        $trendsData = $this->formatDate($this->trendsData()->toArray());
        $result = [];

        foreach ($trendsData as $data) {
            $result['labels'][] = $data->label;
            $result['data'][] = $data->data;
        }

        return $result;
    }

    protected function checkDateFormat(array $dates): void
    {
        foreach ($dates as $date) {
            $d = DateTime::createFromFormat('Y-m-d', $date);

            if (!$d || $d->format('Y-m-d') !== $date) {
                throw new InvalidDateFormatException();
            }
        }
    }
}
