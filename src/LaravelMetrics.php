<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics;

use Carbon\Carbon;
use Eliseekn\LaravelMetrics\Enums\Aggregate;
use Eliseekn\LaravelMetrics\Enums\Period;
use Eliseekn\LaravelMetrics\Exceptions\InvalidAggregateException;
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
    use DatesFunctions;

    protected string $table;

    protected string $column = 'id';

    protected string|array|null $period;

    protected string $aggregate;

    protected string $dateColumn;

    protected ?string $labelColumn = null;

    protected int $count = 0;

    protected int $year;

    protected int $month;

    protected int $day;

    protected int $week;

    protected string $dateIsoFormat = 'YYYY-MM-DD';

    protected bool $fillMissingData = false;

    protected ?array $missingDataLabels = null;

    protected int $missingDataValue = 0;

    protected string $groupBy;

    public function __construct(protected Builder|QueryBuilder $builder)
    {
        $this->table = $this->builder->from;
        $this->dateColumn = $this->table.'.created_at';
        $this->period = null;
        $this->aggregate = Aggregate::COUNT->value;
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
        $this->day = Carbon::now()->day;
        $this->week = Carbon::now()->week;
        $this->groupBy = Period::DAY->value;
    }

    public static function query(Builder|QueryBuilder $builder): self
    {
        return new self($builder);
    }

    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    protected function by(string $period, int $count = 0): self
    {
        $period = strtolower($period);

        if (! in_array($period, Period::values())) {
            throw new InvalidPeriodException();
        }

        $this->period = $period;
        $this->count = $count;

        return $this;
    }

    public function byDay(int $count = 0): self
    {
        return $this->by(Period::DAY->value, $count);
    }

    public function byWeek(int $count = 0): self
    {
        return $this->by(Period::WEEK->value, $count);
    }

    public function byMonth(int $count = 0): self
    {
        return $this->by(Period::MONTH->value, $count);
    }

    public function byYear(int $count = 0): self
    {
        return $this->by(Period::YEAR->value, $count);
    }

    public function between(string $start, string $end, string $dateIsoFormat = 'YYYY-MM-DD'): self
    {
        $this->checkDateFormat([$start, $end]);
        $this->period = [$start, $end];
        $this->dateIsoFormat = $dateIsoFormat;

        return $this;
    }

    protected function groupBy(string $period): self
    {
        $this->groupBy = $period;

        return $this;
    }

    public function groupByYear(): self
    {
        return $this->groupBy(Period::YEAR->value);
    }

    public function groupByMonth(): self
    {
        return $this->groupBy(Period::MONTH->value);
    }

    public function groupByWeek(): self
    {
        return $this->groupBy(Period::WEEK->value);
    }

    public function groupByDay(): self
    {
        return $this->groupBy(Period::DAY->value);
    }

    public function forDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function forWeek(int $week): self
    {
        $this->week = $week;

        return $this;
    }

    public function forMonth(int $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function forYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    protected function aggregate(string $aggregate, string $column): self
    {
        $aggregate = strtolower($aggregate);

        if (! in_array($aggregate, Aggregate::values())) {
            throw new InvalidAggregateException();
        }

        $this->aggregate = $aggregate;
        $this->column = $this->table.'.'.$column;

        return $this;
    }

    public function count(string $column = 'id'): self
    {
        return $this->aggregate(Aggregate::COUNT->value, $column);
    }

    public function average(string $column): self
    {
        return $this->aggregate(Aggregate::AVERAGE->value, $column);
    }

    public function sum(string $column): self
    {
        return $this->aggregate(Aggregate::SUM->value, $column);
    }

    public function max(string $column): self
    {
        return $this->aggregate(Aggregate::MAX->value, $column);
    }

    public function min(string $column): self
    {
        return $this->aggregate(Aggregate::MIN->value, $column);
    }

    protected function countBy(string $period, string $column = 'id', int $count = 0): self
    {
        return $this
            ->by($period, $count)
            ->aggregate(Aggregate::COUNT->value, $column);
    }

    protected function averageBy(string $period, string $column = 'id', int $count = 0): self
    {
        return $this
            ->by($period, $count)
            ->aggregate(Aggregate::AVERAGE->value, $column);
    }

    protected function sumBy(string $period, string $column = 'id', int $count = 0): self
    {
        return $this
            ->by($period, $count)
            ->aggregate(Aggregate::SUM->value, $column);
    }

    protected function maxBy(string $period, string $column = 'id', int $count = 0): self
    {
        return $this
            ->by($period, $count)
            ->aggregate(Aggregate::MAX->value, $column);
    }

    protected function minBy(string $period, string $column = 'id', int $count = 0): self
    {
        return $this
            ->by($period, $count)
            ->aggregate(Aggregate::MIN->value, $column);
    }

    public function countByDay(string $column = 'id', int $count = 0): self
    {
        return $this->countBy(Period::DAY->value, $column, $count);
    }

    public function countByWeek(string $column = 'id', int $count = 0): self
    {
        return $this->countBy(Period::WEEK->value, $column, $count);
    }

    public function countByMonth(string $column = 'id', int $count = 0): self
    {
        return $this->countBy(Period::MONTH->value, $column, $count);
    }

    public function countByYear(string $column = 'id', int $count = 0): self
    {
        return $this->countBy(Period::YEAR->value, $column, $count);
    }

    public function sumByDay(string $column, int $count = 0): self
    {
        return $this->sumBy(Period::DAY->value, $column, $count);
    }

    public function sumByWeek(string $column, int $count = 0): self
    {
        return $this->sumBy(Period::WEEK->value, $column, $count);
    }

    public function sumByMonth(string $column, int $count = 0): self
    {
        return $this->sumBy(Period::MONTH->value, $column, $count);
    }

    public function sumByYear(string $column, int $count = 0): self
    {
        return $this->sumBy(Period::YEAR->value, $column, $count);
    }

    public function averageByDay(string $column, int $count = 0): self
    {
        return $this->averageBy(Period::DAY->value, $column, $count);
    }

    public function averageByWeek(string $column, int $count = 0): self
    {
        return $this->averageBy(Period::WEEK->value, $column, $count);
    }

    public function averageByMonth(string $column, int $count = 0): self
    {
        return $this->averageBy(Period::MONTH->value, $column, $count);
    }

    public function averageByYear(string $column, int $count = 0): self
    {
        return $this->averageBy(Period::YEAR->value, $column, $count);
    }

    public function maxByDay(string $column, int $count = 0): self
    {
        return $this->maxBy(Period::DAY->value, $column, $count);
    }

    public function maxByWeek(string $column, int $count = 0): self
    {
        return $this->maxBy(Period::WEEK->value, $column, $count);
    }

    public function maxByMonth(string $column, int $count = 0): self
    {
        return $this->maxBy(Period::MONTH->value, $column, $count);
    }

    public function maxByYear(string $column, int $count = 0): self
    {
        return $this->maxBy(Period::YEAR->value, $column, $count);
    }

    public function minByDay(string $column, int $count = 0): self
    {
        return $this->minBy(Period::DAY->value, $column, $count);
    }

    public function minByWeek(string $column, int $count = 0): self
    {
        return $this->minBy(Period::WEEK->value, $column, $count);
    }

    public function minByMonth(string $column, int $count = 0): self
    {
        return $this->minBy(Period::MONTH->value, $column, $count);
    }

    public function minByYear(string $column, int $count = 0): self
    {
        return $this->minBy(Period::YEAR->value, $column, $count);
    }

    public function countBetween(array $period, string $column = 'id', string $dateIsoFormat = 'YYYY-MM-DD'): self
    {
        return $this
            ->count($column)
            ->between($period[0], $period[1], $dateIsoFormat);
    }

    public function sumBetween(array $period, string $column, string $dateIsoFormat = 'YYYY-MM-DD'): self
    {
        return $this
            ->sum($column)
            ->between($period[0], $period[1], $dateIsoFormat);
    }

    public function averageBetween(array $period, string $column, string $dateIsoFormat = 'YYYY-MM-DD'): self
    {
        return $this
            ->average($column)
            ->between($period[0], $period[1], $dateIsoFormat);
    }

    public function maxBetween(array $period, string $column, string $dateIsoFormat = 'YYYY-MM-DD'): self
    {
        return $this
            ->max($column)
            ->between($period[0], $period[1], $dateIsoFormat);
    }

    public function minBetween(array $period, string $column, string $dateIsoFormat = 'YYYY-MM-DD'): self
    {
        return $this
            ->min($column)
            ->between($period[0], $period[1], $dateIsoFormat);
    }

    public function dateColumn(string $column): self
    {
        $this->dateColumn = $this->table.'.'.$column;

        return $this;
    }

    public function labelColumn(string $column): self
    {
        $this->labelColumn = $this->table.'.'.$column;

        return $this;
    }

    public function fillMissingData(int $missingDataValue = 0, array $missingDataLabels = null): self
    {
        $this->fillMissingData = true;
        $this->missingDataLabels = $missingDataLabels;
        $this->missingDataValue = $missingDataValue;

        return $this;
    }

    protected function metricsData(): mixed
    {
        if (is_array($this->period)) {
            return $this->builder
                ->selectRaw($this->asData())
                ->whereBetween(DB::raw("date($this->dateColumn)"), [$this->period[0], $this->period[1]])
                ->first();
        }

        return match ($this->period) {
            Period::DAY->value => $this->builder
                ->selectRaw($this->asData())
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw("day($this->dateColumn)"), $this->day);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw("day($this->dateColumn)"), $this->getDayPeriod());
                })
                ->first(),

            Period::WEEK->value => $this->builder
                ->selectRaw($this->asData())
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(Period::WEEK->value)), $this->week);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(Period::WEEK->value)), $this->getWeekPeriod());
                })
                ->first(),

            Period::MONTH->value => $this->builder
                ->selectRaw($this->asData())
                ->whereYear($this->dateColumn, $this->year)
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(Period::MONTH->value)), $this->month);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(Period::MONTH->value)), $this->getMonthPeriod());
                })
                ->first(),

            Period::YEAR->value => $this->builder
                ->selectRaw($this->asData())
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(Period::YEAR->value)), $this->year);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(Period::YEAR->value)), [
                        Carbon::now()->subYears($this->count)->year, $this->year,
                    ]);
                })
                ->first(),

            default => $this->builder
                ->selectRaw($this->asData())
                ->first(),
        };
    }

    protected function trendsData(): Collection
    {
        if (is_array($this->period)) {
            return $this->builder
                ->selectRaw($this->asData().', '.$this->asLabel("date($this->dateColumn)", false))
                ->whereBetween(DB::raw("date($this->dateColumn)"), [$this->period[0], $this->period[1]])
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        }

        return match ($this->period) {
            Period::DAY->value => $this->builder
                ->selectRaw($this->asData().', '.$this->asLabel(Period::DAY->value))
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw("day($this->dateColumn)"), $this->day);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw("day($this->dateColumn)"), $this->getDayPeriod());
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            Period::WEEK->value => $this->builder
                ->selectRaw($this->asData().', '.$this->asLabel(Period::WEEK->value))
                ->whereYear($this->dateColumn, $this->year)
                ->whereMonth($this->dateColumn, $this->month)
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(Period::WEEK->value)), $this->week);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(Period::WEEK->value)), $this->getWeekPeriod());
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            Period::MONTH->value => $this->builder
                ->selectRaw($this->asData().', '.$this->asLabel(Period::MONTH->value))
                ->whereYear($this->dateColumn, $this->year)
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(Period::MONTH->value)), $this->month);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(Period::MONTH->value)), $this->getMonthPeriod());
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            Period::YEAR->value => $this->builder
                ->selectRaw($this->asData().', '.$this->asLabel(Period::YEAR->value))
                ->when($this->count === 1, function (Builder|QueryBuilder $query) {
                    return $query->where(DB::raw($this->formatPeriod(Period::YEAR->value)), $this->year);
                })
                ->when($this->count > 1, function (Builder|QueryBuilder $query) {
                    return $query->whereBetween(DB::raw($this->formatPeriod(Period::YEAR->value)), [
                        Carbon::now()->subYears($this->count)->year, $this->year,
                    ]);
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get(),

            default => $this->builder
                ->selectRaw($this->asData().', '.$this->asLabel())
                ->groupBy('label')
                ->orderBy('label')
                ->get(),
        };
    }

    protected function asData(): string
    {
        return "$this->aggregate($this->column) as data";
    }

    protected function asLabel(string $label = null, bool $format = true): string
    {
        if (is_null($this->labelColumn)) {
            $label = ! $format ? $label : $this->formatPeriod($label);
        } else {
            $label = $this->labelColumn;
        }

        return $label.' as label';
    }

    protected function populateMissingDataForPeriod(array $data): array
    {
        $dates = $this->getCustomPeriod();
        $data = collect($data);
        $result = [];

        foreach ($dates as $date) {
            $dataForDate = $data->where('label', $date)->first();

            if ($dataForDate) {
                $result[] = [
                    'label' => $dataForDate['label'],
                    'data' => $dataForDate['data'],
                ];
            } else {
                $result[] = [
                    'label' => $date,
                    'data' => $this->missingDataValue,
                ];
            }
        }

        $result = $this->formatDate($result);

        return $this->formatTrends($result);
    }

    protected function populateMissingData(array $labels, array $data): array
    {
        $result = [
            'labels' => [],
            'data' => [],
        ];

        foreach ($labels as $label => $defaultValue) {
            $key = array_search($label, $data['labels']);
            $result['labels'][] = $label;

            if ($key !== false) {
                $result['data'][] = $data['data'][$key];
            } else {
                $result['data'][] = $defaultValue;
            }
        }

        return $result;
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
        $trendsData = $this
            ->trendsData()
            ->toArray();

        $trendsData = array_map(fn ($datum) => (array) $datum, $trendsData);

        if (! $this->fillMissingData) {
            $trendsData = $this->formatDate($trendsData);

            return $this->formatTrends($trendsData);
        } else {
            if (! is_null($this->labelColumn)) {
                $trendsData = $this->formatTrends($trendsData);

                return $this->populateMissingData($this->getLabelsData(), $trendsData);
            }

            if (is_array($this->period)) {
                return $this->populateMissingDataForPeriod($trendsData);
            }

            if (is_string($this->period)) {
                $trendsData = $this->formatDate($trendsData);

                return $this->populateMissingData($this->getPeriod(), $this->formatTrends($trendsData));
            }
        }

        return [
            'labels' => [],
            'data' => [],
        ];
    }

    protected function formatTrends(array $data): array
    {
        $result = [
            'labels' => [],
            'data' => [],
        ];

        foreach ($data as $datum) {
            $result['labels'][] = $datum['label'];
            $result['data'][] = $datum['data'];
        }

        return $result;
    }

    protected function locale(): string
    {
        return Config::get('app.locale');
    }
}
