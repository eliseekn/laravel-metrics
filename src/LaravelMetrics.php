<?php

namespace Eliseekn\LaravelMetrics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * LaravelMetrics
 * 
 * Generate metrics and trends data from your database
 */
class LaravelMetrics
{
    public const TODAY = 'today';
    public const DAY = 'day';
    public const WEEK = 'week';
    public const MONTH = 'month';
    public const YEAR = 'year';
    public const QUATER_YEAR = 'quater_year';
    public const HALF_YEAR = 'half_year';

    public const COUNT = 'COUNT';
    public const AVERAGE = 'AVG';
    public const SUM = 'SUM';
    public const MAX = 'MAX';
    public const MIN = 'MIN';

    private static function getMetricsData(string $table, string $column, string $period, string $type, ?string $whereRaw = null)
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $week = Carbon::now()->weekOfYear;
        
        if (!in_array($period, [self::TODAY, self::DAY, self::WEEK, self::MONTH, self::YEAR, self::QUATER_YEAR, self::HALF_YEAR])) {
            if (!str_contains('~', $period)) return null;
            
            list($start_date, $end_date) = explode('~', $period);
            $start_date = Carbon::parse($start_date)->toDateString();
            $end_date = Carbon::parse($end_date)->toDateString();

            return DB::table($table)
                ->selectRaw("$type($column) as data")
                ->whereBetween(DB::raw('date(created_at)'), [$start_date, $end_date])
                ->where(function ($q) use ($whereRaw) {
                    if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                })
                ->first();
        }

        switch($period) {
            case self::TODAY: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->where(DB::raw('date(created_at)'), Carbon::now()->toDateString())
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();

            case self::DAY: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(DB::raw('month(created_at)'), $month)
                    ->where(DB::raw('week(created_at)'), $week)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();

            case self::WEEK: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(DB::raw('month(created_at)'), $month)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();

            case self::MONTH: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();

            case self::YEAR: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();

            case self::HALF_YEAR: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->whereBetween(DB::raw('month(created_at)'), [Carbon::now()->subMonths(6)->month, $month])
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();
    
            case self::QUATER_YEAR: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data")
                    ->whereBetween(DB::raw('month(created_at)'), [Carbon::now()->subMonths(3)->month, $month])
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->first();

            default: return null;
        }
    }

    private static function getTrendsData(string $table, string $column, string $period, string $type, ?string $whereRaw = null)
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $week = Carbon::now()->weekOfYear;

        if (!in_array($period, [self::TODAY, self::DAY, self::WEEK, self::MONTH, self::YEAR, self::QUATER_YEAR, self::HALF_YEAR])) {
            if (!str_contains('~', $period)) return null;
            
            list($start_date, $end_date) = explode('~', $period);

            $start_date = Carbon::parse($start_date)->toDateString();
            $end_date = Carbon::parse($end_date)->toDateString();

            return DB::table($table)
                ->selectRaw("$type($column) as data, date(created_at) as label")
                ->whereBetween(DB::raw('date(created_at)'), [$start_date, $end_date])
                ->where(function ($q) use ($whereRaw) {
                    if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                })
                ->first();
        }

        switch($period) {
            case self::TODAY: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, dayname(created_at) as label, weekday(created_at) as week_day")
                    ->where(DB::raw('date(created_at)'), Carbon::now()->toDateString())
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label', 'week_day')
                    ->orderBy('week_day')
                    ->get();

            case self::DAY: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, dayname(created_at) as label, weekday(created_at) as week_day")
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(DB::raw('month(created_at)'), $month)
                    ->where(DB::raw('week(created_at)'), $week)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label', 'week_day')
                    ->orderBy('week_day')
                    ->get();

            case self::WEEK: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, dayname(created_at) as label, weekday(created_at) as week_day")
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(DB::raw('month(created_at)'), $month)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label', 'week_day')
                    ->orderBy('week_day')
                    ->get();

            case self::MONTH: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, monthname(created_at) as label, month(created_at) as month")
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label', 'month')
                    ->orderBy('month')
                    ->get();

            case self::YEAR: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, year(created_at) as label")
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();

            case self::HALF_YEAR: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, monthname(created_at) as label, month(created_at) as month")
                    ->whereBetween(DB::raw('month(created_at)'), [Carbon::now()->subMonths(6)->month, $month])
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label', 'month')
                    ->orderBy('month')
                    ->get();
    
            case self::QUATER_YEAR: 
                return DB::table($table)
                    ->selectRaw("$type($column) as data, monthname(created_at) as label, month(created_at) as month")
                    ->whereBetween(DB::raw('month(created_at)'), [Carbon::now()->subMonths(3)->month, $month])
                    ->where(DB::raw('year(created_at)'), $year)
                    ->where(function ($q) use ($whereRaw) {
                        if (!is_null($whereRaw)) $q->whereRaw($whereRaw);
                    })
                    ->groupBy('label', 'month')
                    ->orderBy('month')
                    ->get();

            default: return [];
        }
    }
    
    /**
     * Generate metrics data
     *
     * @param  string $table
     * @param  string $column
     * @param  string $period
     * @param  string $type
     * @param  string|null $whereRaw
     * @return int
     */
    public static function getMetrics(string $table, string $column, string $period, string $type, ?string $whereRaw = null): int
    {
        $metricsData = self::getMetricsData($table, $column, $period, $type, $whereRaw);

        return is_null($metricsData) ? 0 : (int) $metricsData->data;
    }
    
    /**
     * Generate trends data to use in charts
     *
     * @param  string $table
     * @param  string $column
     * @param  string $period
     * @param  string $type
     * @param  string|null $whereRaw
     * @return array
     */
    public static function getTrends(string $table, string $column, string $period, string $type, ?string $whereRaw = null): array
    {
        $trendsData = self::getTrendsData($table, $column, $period, $type, $whereRaw);
        $result = [];

        foreach ($trendsData as $data) {
            $result['labels'][] = $data->label;
            $result['data'][] = (int) $data->data;
        }

        return $result;
    }
}
