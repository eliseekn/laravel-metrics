<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;
use Eliseekn\LaravelMetrics\Enums\Period;
use Eliseekn\LaravelMetrics\Exceptions\InvalidDateFormatException;
use Illuminate\Support\Facades\DB;

trait DatesFunctions
{
    protected function carbon(): Carbon
    {
        return Carbon::parse($this->year.'-'.$this->month.'-'.$this->day);
    }

    protected function getDayPeriod(): array
    {
        $day = $this->month !== Carbon::now()->month ? $this->carbon()->endOfMonth()->day : $this->day;
        $diff = $day - $this->carbon()->startOfMonth()->day;

        if ($diff < $this->count) {
            return [$this->carbon()->startOfMonth()->day, $day];
        }

        return [$this->carbon()->subDays($this->count)->day, $day];
    }

    protected function getWeekPeriod(): array
    {
        $week = $this->month !== Carbon::now()->month ? $this->carbon()->endOfMonth()->week : $this->week;
        $diff = $week - $this->carbon()->startOfMonth()->week;

        if ($diff < $this->count) {
            return [$this->carbon()->startOfMonth()->week, $week];
        }

        return [$this->carbon()->subWeeks($this->count)->week, $week];
    }

    protected function getMonthPeriod(): array
    {
        $month = $this->year !== Carbon::now()->year ? $this->carbon()->endOfYear()->month : $this->month;
        $diff = $month - $this->carbon()->startOfYear()->month;

        if ($diff < $this->count) {
            return [$this->carbon()->startOfYear()->month, $month];
        }

        return [$this->carbon()->subMonths($this->count)->month, $month];
    }

    protected function formatPeriod(?string $period = null): string
    {
        $driver = $this->builder->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            return match ($period) {
                Period::TODAY->value, Period::DAY->value => "day($this->dateColumn)",
                Period::WEEK->value => "week($this->dateColumn)",
                Period::MONTH->value => "month($this->dateColumn)",
                Period::YEAR->value => "year($this->dateColumn)",
                default => $this->dateColumn,
            };
        }

        if ($driver === 'pgsql') {
            return match ($period) {
                Period::TODAY->value, Period::DAY->value => "EXTRACT(DAY FROM $this->dateColumn)",
                Period::WEEK->value => "EXTRACT(WEEK FROM $this->dateColumn)",
                Period::MONTH->value => "EXTRACT(MONTH FROM $this->dateColumn)",
                Period::YEAR->value => "EXTRACT(YEAR FROM $this->dateColumn)",
                default => $this->dateColumn,
            };
        }

        return match ($period) {
            Period::TODAY->value, Period::DAY->value => "CAST(strftime('%d', $this->dateColumn) AS INTEGER)",
            Period::WEEK->value => "CAST(strftime('%W', $this->dateColumn) AS INTEGER)",
            Period::MONTH->value => "CAST(strftime('%m', $this->dateColumn) AS INTEGER)",
            Period::YEAR->value => "CAST(strftime('%Y', $this->dateColumn) AS INTEGER)",
            default => $this->dateColumn,
        };
    }

    protected function formatDateColumn(): string
    {
        $driver = $this->builder->getConnection()->getDriverName();

        return match ($driver) {
            'mysql' => "date($this->dateColumn)",
            'pgsql' => "TO_CHAR($this->dateColumn, 'YYYY-MM-DD')",
            default => "strftime('%Y-%m-%d', $this->dateColumn)",
        };
    }

    protected function formatDate(array $data): array
    {
        return array_map(function ($datum) {
            if (! is_numeric($datum['label']) && ! DateTime::createFromFormat('Y-m-d', $datum['label'])) {
                return $datum;
            }

            if ($this->period === Period::MONTH->value) {
                $datum['label'] = Carbon::parse(sprintf('%04d-%02d', $this->year, (int) $datum['label']))->locale($this->locale())->monthName;
            } elseif ($this->period === Period::DAY->value) {
                $datum['label'] = Carbon::parse(sprintf('%04d-%02d-%02d', $this->year, $this->month, (int) $datum['label']))->locale($this->locale())->dayName;
            } elseif ($this->period === Period::WEEK->value) {
                $datum['label'] = 'Week '.$datum['label'];
            } elseif ($this->period === Period::YEAR->value) {
                $datum['label'] = intval($datum['label']);
            } else {
                $datum['label'] = Carbon::parse($datum['label'])->locale($this->locale())->isoFormat($this->dateIsoFormat);
            }

            return $datum;
        }, $data);
    }

    protected function checkDateFormat(array $dates): void
    {
        foreach ($dates as $date) {
            $d = DateTime::createFromFormat('Y-m-d', $date);

            if (! $d || $d->format('Y-m-d') !== $date) {
                throw new InvalidDateFormatException;
            }
        }
    }

    protected function getMonthsData(): array
    {
        $result = [];

        $endDate = $this->year < Carbon::now()->year
            ? $this->carbon()->endOfYear()->format('Y-m-d')
            : $this->carbon()->format('Y-m-d');

        $dates = collect(
            new CarbonPeriod(
                $this->carbon()->startOfYear()->format('Y-m-d'),
                '1 month',
                $endDate
            ))
            ->map(fn (Carbon $date) => $date->locale($this->locale())->monthName)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getDaysData(): array
    {
        $result = [];

        $dates = collect(
            new CarbonPeriod(
                $this->carbon()->startOfWeek()->format('Y-m-d'),
                '1 day',
                $this->carbon()->format('Y-m-d')
            ))
            ->map(fn (Carbon $date) => $date->locale($this->locale())->dayName)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getWeeksData(): array
    {
        $result = [];

        $dates = collect(
            new CarbonPeriod(
                $this->carbon()->startOfMonth()->format('Y-m-d'),
                '1 week',
                $this->carbon()->format('Y-m-d')
            ))
            ->map(fn (Carbon $date) => 'Week '.$date->locale($this->locale())->week)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getYearsData(): array
    {
        $result = [];

        $dates = collect(
            new CarbonPeriod(
                $this->carbon()->subYears($this->count)->format('Y-m-d'),
                '1 year',
                $this->carbon()->format('Y-m-d')
            ))
            ->map(fn (Carbon $date) => $date->locale($this->locale())->year)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getLabelsData(): array
    {
        $result = [];

        $labelColumn = explode('.', $this->labelColumn)[1];

        $missingDataLabels = empty($this->missingDataLabels)
            ? DB::table($this->table)->distinct()->pluck($labelColumn)->toArray()
            : $this->missingDataLabels;

        foreach ($missingDataLabels as $label) {
            $result[$label] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getCustomPeriod(): array
    {
        return collect(
            new CarbonPeriod(
                $this->period[0],
                '1 '.$this->groupBy,
                $this->period[1]
            ))
            ->map(fn (Carbon $date) => $date->format('Y-m-d'))->toArray();
    }

    protected function getPeriod(): array
    {
        return match ($this->period) {
            Period::MONTH->value => $this->getMonthsData(),
            Period::DAY->value, Period::TODAY->value => $this->getDaysData(),
            Period::WEEK->value => $this->getWeeksData(),
            default => $this->getYearsData()
        };
    }
}
