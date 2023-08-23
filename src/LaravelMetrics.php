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
 * Generate easily metrics and trends data of your models for your dashboards.
 */
class LaravelMetrics
{
    protected const DAY = 'day';
    protected const WEEK = 'week';
    protected const MONTH = 'month';
    protected const YEAR = 'year';
    protected const COUNT = 'count';
    protected const AVERAGE = 'avg';
    protected const SUM = 'sum';
    protected const MAX = 'max';
    protected const MIN = 'min';

    protected string $table;
    protected string $column = 'id';
    protected string|array $period = self::MONTH;
    protected string $type = self::COUNT;
    protected string $dateColumn;
    protected int $count = 0;
    protected int $year;
    protected int $month;
    protected int $day;
    protected int $week;

    public function __construct(protected Builder $builder)
    {
        $this->table = $this->builder->from;
        $this->dateColumn = $this->table . '.created_at';
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->day = Carbon::now()->day;
        $this->week = Carbon::now()->week;
    }

    public static function query(Builder $builder): self
    {
        return new self($builder);
    }

    public function by(string $period, int $count = 0): self
    {
        $period = strtolower($period);

        if (!in_array($period, [self::DAY, self::WEEK, self::MONTH, self::YEAR])) {
            throw new InvalidPeriodException();
        }

        $this->period = $period;
        $this->count = $count;
        return $this;
    }

    public function byDay(int $count = 0): self
    {
        return $this->by(self::DAY, $count);
    }

    public function byWeek(int $count = 0): self
    {
        return $this->by(self::WEEK, $count);
    }

    public function byMonth(int $count = 0): self
    {
        return $this->by(self::MONTH, $count);
    }

    public function byYear(int $count = 0): self
    {
        return $this->by(self::YEAR, $count);
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
                ->where(DB::raw($this->formatPeriod(self::WEEK)), $this->week)
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw("day($this->dateColumn)"), $this->day);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw("day($this->dateColumn)"), [
                        Carbon::now()->subDays($this->count)->day, $this->day
                    ]);
                })
                ->first(),

            self::WEEK => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(self::WEEK)), $this->week);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::WEEK)), [
                        Carbon::now()->subWeeks($this->count)->week, $this->week
                    ]);
                })
                ->first(),

            self::MONTH => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->whereYear($this->dateColumn, $this->year)
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(self::MONTH)), $this->month);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::MONTH)), [
                        $this->parseMonth(), $this->month
                    ]);
                })
                ->first(),

            self::YEAR => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data")
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(self::YEAR)), $this->year);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
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
                ->whereBetween(DB::raw("date($this->dateColumn)"), [$this->period[0], $this->period[1]])
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        }

        return match ($this->period) {
            self::DAY => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::DAY) . " as label")
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->where(DB::raw($this->formatPeriod(self::WEEK)), $this->week)
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw("day($this->dateColumn)"), $this->day);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw("day($this->dateColumn)"), [
                        Carbon::now()->subDays($this->count)->day, $this->day
                    ]);
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            self::WEEK => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::WEEK) . " as label")
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(self::WEEK)), $this->week);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::WEEK)), [
                        Carbon::now()->subWeeks($this->count)->week, $this->week
                    ]);
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            self::MONTH => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::MONTH) . " as label")
                ->whereYear($this->dateColumn, $this->year)
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(self::MONTH)), $this->month);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::MONTH)), [
                        $this->parseMonth(), $this->month
                    ]);
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            self::YEAR => $this->builder
                ->toBase()
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::YEAR) . " as label")
                ->when($this->count === 1, function (QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(self::YEAR)), $this->year);
                })
                ->when($this->count > 1, function (QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::YEAR)), [
                        Carbon::now()->subYears($this->count)->year, $this->year
                    ]);
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            default => [],
        };
    }

    protected function parseMonth(): int
    {
        $diff = $this->month - Carbon::now()->startOfYear()->month;

        if ($diff < $this->count) {
            return Carbon::now()->startOfYear()->month;
        }

        return Carbon::now()->subMonths($this->count)->month;
    }

    protected function locale(): string
    {
        return Config::get('app.locale');
    }

    protected function formatPeriod(string $period): string
    {
        return match ($period) {
            self::DAY => "weekday($this->dateColumn)",
            self::WEEK => "week($this->dateColumn)",
            self::MONTH => "month($this->dateColumn)",
            self::YEAR => "year($this->dateColumn)",
            default => '',
        };
    }

    protected function formatDate(array $data): array
    {
        return array_map(function ($data)  {
            if ($this->period === self::MONTH) {
                $data->label = Carbon::parse($this->year . '-' . $data->label)->locale(self::locale())->monthName;
            } elseif ($this->period === self::DAY) {
                $data->label = Carbon::parse($this->year . '-' . $this->month . '-' . $data->label)->locale(self::locale())->dayName;
            } elseif ($this->period === self::WEEK) {
                $data->label = 'Week ' . $data->label;
            } elseif ($this->period === self::YEAR) {
                $data->label = intval($data->label);
            } else {
                $data->label = Carbon::parse($data->label)->locale(self::locale())->toFormattedDateString();
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
