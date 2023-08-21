<?php
declare(strict_types=1);

namespace Eliseekn\LaravelMetrics;

use Carbon\Carbon;
use DateTime;
use Eliseekn\LaravelMetrics\Exceptions\InvalidDateFormatException;
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

    protected string $column = 'id';
    protected string|array $period = self::MONTH;
    protected string $type = self::COUNT;
    protected int $count = 0;
    protected string $whereRaw = '';

    public function __construct(
        protected string $table,
        protected ?int $year = null,
        protected ?int $month = null,
        protected ?int $day = null
    ) {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->day = Carbon::now()->day;
    }

    public static function table(string $table): self
    {
        return new self($table);
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

    public function average(string $column = 'id'): self
    {
        $this->type = self::AVERAGE;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function sum(string $column = 'id'): self
    {
        $this->type = self::SUM;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function max(string $column = 'id'): self
    {
        $this->type = self::MAX;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    public function min(string $column = 'id'): self
    {
        $this->type = self::MIN;
        $this->column = $this->table . '.' . $column;
        return $this;
    }

    protected function metricsData(): mixed
    {
        if (is_array($this->period)) {
            return DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data")
                ->whereBetween(DB::raw('date(' . $this->table  . '.created_at)'), [$this->period[0], $this->period[1]])
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->first();
        }

        return match ($this->period) {
            self::DAY => DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data")
                ->whereYear($this->table . '.created_at', $this->year)
                ->whereMonth($this->table . '.created_at', $this->month)
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->when($this->count > 0, function ($query) {
                    $column = self::driver() === 'sqlite'
                        ? "strftime('%d', ' . $this->table  . '.created_at)"
                        : 'day(' . $this->table  . '.created_at)';

                    return $query->whereBetween(DB::raw($column), [
                        Carbon::now()->subDays($this->count)->day, $this->day
                    ]);
                })
                ->first(),

            self::MONTH => DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data")
                ->whereYear($this->table . '.created_at', $this->year)
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->when($this->count > 0, function ($query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::MONTH)), [
                        Carbon::now()->subMonths($this->count)->month, $this->month
                    ]);
                })
                ->first(),

            self::YEAR => DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data")
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->when($this->count > 0, function ($query) {
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
            return DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data, date(created_at) as label")
                ->whereBetween(DB::raw('date(' . $this->table  . '.created_at)'), [$this->period[0], $this->period[1]])
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->groupBy('label')
                ->get();
        }

        return match ($this->period) {
            self::DAY => DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::DAY) . " as label")
                ->whereYear($this->table . '.created_at', $this->year)
                ->whereMonth($this->table . '.created_at', $this->month)
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->when($this->count > 0, function ($query) {
                    $column = self::driver() === 'sqlite'
                        ? "strftime('%d', ' . $this->table  . '.created_at)"
                        : 'day(' . $this->table  . '.created_at)';

                    return $query->whereBetween(DB::raw($column), [
                        Carbon::now()->subDays($this->count)->day, $this->day
                    ]);
                })
                ->groupBy('label')
                ->get(),

            self::MONTH => DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::MONTH) . " as label")
                ->whereYear($this->table . '.created_at', $this->year)
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->when($this->count > 0, function ($query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(self::MONTH)), [
                        Carbon::now()->subMonths($this->count)->month, $this->month
                    ]);
                })
                ->groupBy('label')
                ->get(),

            self::YEAR => DB::table($this->table)
                ->selectRaw("$this->type($this->column) as data, " . $this->formatPeriod(self::YEAR) . " as label")
                ->when(!empty($this->whereRaw), fn ($query) => $query->whereRaw($this->whereRaw))
                ->when($this->count > 0, function ($query) {
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
            self::DAY => self::driver() === 'sqlite' ? "strftime('%w', ' . $this->table  . '.created_at)" : 'weekday(' . $this->table  . '.created_at)',
            self::MONTH => self::driver() === 'sqlite' ? "strftime('%m', ' . $this->table  . '.created_at)" : 'month(' . $this->table  . '.created_at)',
            self::YEAR => self::driver() === 'sqlite' ? "strftime('%Y', ' . $this->table  . '.created_at)" : 'year(' . $this->table  . '.created_at)',
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
