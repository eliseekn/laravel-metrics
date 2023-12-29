<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;
use Eliseekn\LaravelMetrics\Enums\Period;
use Eliseekn\LaravelMetrics\Exceptions\InvalidDateFormatException;

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

    protected function formatPeriod(string $period): string
    {
        $driver = $this->builder->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            return match ($period) {
                Period::DAY->value => "weekday($this->dateColumn)",
                Period::WEEK->value => "week($this->dateColumn)",
                Period::MONTH->value => "month($this->dateColumn)",
                default => "year($this->dateColumn)",
            };
        }

        if ($driver === 'pgsql') {
            return match ($period) {
                Period::DAY->value => "EXTRACT(DOW FROM $this->dateColumn)",
                Period::WEEK->value => "EXTRACT(WEEK FROM $this->dateColumn)",
                Period::MONTH->value => "EXTRACT(MONTH FROM $this->dateColumn)",
                default => "EXTRACT(YEAR FROM $this->dateColumn)",
            };
        }

        return match ($period) {
            Period::DAY->value => "strftime('%w', $this->dateColumn)",
            Period::WEEK->value => "strftime('%W', $this->dateColumn)",
            Period::MONTH->value => "strftime('%m', $this->dateColumn)",
            default => "strftime('%Y', $this->dateColumn)",
        };
    }

    protected function formatDate(array $data): array
    {
        return array_map(function ($datum) {
            if (! is_numeric($datum['label']) && ! DateTime::createFromFormat('Y-m-d', $datum['label'])) {
                return $datum;
            }

            if ($this->period === Period::MONTH->value) {
                $datum['label'] = Carbon::parse($this->year.'-'.$datum['label'])->locale(self::locale())->monthName;
            } elseif ($this->period === Period::DAY->value) {
                $datum['label'] = Carbon::parse($this->year.'-'.$this->month.'-'.$datum['label'])->locale(self::locale())->dayName;
            } elseif ($this->period === Period::WEEK->value) {
                $datum['label'] = 'Week '.$datum['label'];
            } elseif ($this->period === Period::YEAR->value) {
                $datum['label'] = intval($datum['label']);
            } else {
                $datum['label'] = Carbon::parse($datum['label'])->locale(self::locale())->isoFormat($this->dateIsoFormat);
            }

            return $datum;
        }, $data);
    }

    protected function checkDateFormat(array $dates): void
    {
        foreach ($dates as $date) {
            $d = DateTime::createFromFormat('Y-m-d', $date);

            if (! $d || $d->format('Y-m-d') !== $date) {
                throw new InvalidDateFormatException();
            }
        }
    }

    protected function getMonthsData(): array
    {
        $result = [];

        $dates = collect(
            CarbonPeriod::between(
                $this->carbon()->startOfYear()->format('Y-m-d'),
                $this->carbon()->format('Y-m-d')
            )->interval('1 month'))
            ->map(fn ($date) => Carbon::parse($date)->locale(self::locale())->monthName)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getDaysData(): array
    {
        $result = [];

        $dates = collect(
            CarbonPeriod::between(
                $this->carbon()->startOfWeek()->format('Y-m-d'),
                $this->carbon()->format('Y-m-d')
            )->interval('1 day'))
            ->map(fn ($date) => Carbon::parse($date)->locale(self::locale())->dayName)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getWeeksData(): array
    {
        $result = [];

        $dates = collect(
            CarbonPeriod::between(
                $this->carbon()->startOfMonth()->format('Y-m-d'),
                $this->carbon()->format('Y-m-d')
            )->interval('1 week'))
            ->map(fn ($date) => 'Week '.Carbon::parse($date)->locale(self::locale())->week)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getYearsData(): array
    {
        $result = [];

        $dates = collect(
            CarbonPeriod::between(
                $this->carbon()->subYears($this->count),
                $this->carbon()
            )->interval('1 year'))
            ->map(fn ($date) => Carbon::parse($date)->locale(self::locale())->year)->toArray();

        foreach ($dates as $date) {
            $result[$date] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getLabelsData(): array
    {
        $result = [];

        foreach ($this->missingDataLabels as $label) {
            $result[$label] = $this->missingDataValue;
        }

        return $result;
    }

    protected function getCustomPeriod(): array
    {
        return collect(
            CarbonPeriod::between(
                $this->period[0],
                $this->period[1]
            )->interval('1 '.$this->groupBy))
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))->toArray();
    }

    protected function getPeriod(): array
    {
        return match ($this->period) {
            Period::MONTH->value => $this->getMonthsData(),
            Period::DAY->value => $this->getDaysData(),
            Period::WEEK->value => $this->getWeeksData(),
            default => $this->getYearsData()
        };
    }
}
