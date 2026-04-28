<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Eliseekn\LaravelMetrics\LaravelMetrics;

class MetricsTest extends TestCase
{
    public function test_returns_zero_when_no_data(): void
    {
        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(1)
            ->metrics();

        $this->assertEquals(0, $result);
    }

    public function test_count_for_specific_month(): void
    {
        $this->insert('2024-03-10');
        $this->insert('2024-03-15');
        $this->insert('2024-03-20');
        $this->insert('2024-04-01'); // excluded — different month

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEquals(3, $result);
    }

    public function test_count_for_specific_year(): void
    {
        $this->insert('2024-01-15');
        $this->insert('2024-06-20');
        $this->insert('2024-12-01');
        $this->insert('2023-06-20'); // excluded — different year

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byYear(1)
            ->forYear(2024)
            ->metrics();

        $this->assertEquals(3, $result);
    }

    public function test_count_for_specific_day(): void
    {
        $this->insert('2024-01-15 10:00:00');
        $this->insert('2024-01-15 14:00:00');
        $this->insert('2024-01-16 10:00:00'); // excluded — different day

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byDay(1)
            ->forYear(2024)
            ->forMonth(1)
            ->forDay(15)
            ->metrics();

        $this->assertEquals(2, $result);
    }

    public function test_count_all_days_of_month_when_count_is_zero(): void
    {
        $this->insert('2024-01-05');
        $this->insert('2024-01-15');
        $this->insert('2024-01-25');
        $this->insert('2024-02-10'); // excluded — different month

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byDay()
            ->forYear(2024)
            ->forMonth(1)
            ->metrics();

        $this->assertEquals(3, $result);
    }

    public function test_count_last_n_days(): void
    {
        // getDayPeriod() for the current month uses $this->day as upper bound.
        // Use today's date so the current-month branch is taken.
        $today = \Carbon\Carbon::now();
        $year = $today->year;
        $month = $today->month;
        $day = $today->day;

        $fmt = fn (int $d) => sprintf('%04d-%02d-%02d', $year, $month, $d);

        $this->insert($fmt(max(1, $day - 2)));
        $this->insert($fmt(max(1, $day - 1)));
        $this->insert($fmt($day));
        if ($day > 5) {
            $this->insert($fmt($day - 5)); // well outside range
        }

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byDay(3)
            ->metrics(); // uses current year/month/day by default

        $this->assertGreaterThanOrEqual(3, $result);
    }

    public function test_count_returns_total_with_no_period(): void
    {
        $this->insert('2022-01-05');
        $this->insert('2023-06-10');
        $this->insert('2024-12-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->metrics();

        $this->assertEquals(3, $result);
    }

    public function test_sum_for_specific_month(): void
    {
        $this->insert('2024-03-10', 'pending', 100.00);
        $this->insert('2024-03-15', 'pending', 200.00);
        $this->insert('2024-03-20', 'pending', 300.00);
        $this->insert('2024-04-01', 'pending', 999.00); // excluded

        $result = LaravelMetrics::query($this->db())
            ->sum('amount')
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEqualsWithDelta(600.00, $result, 0.001);
    }

    public function test_average_returns_float_precision(): void
    {
        $this->insert('2024-03-10', 'pending', 100.00);
        $this->insert('2024-03-15', 'pending', 200.00);
        $this->insert('2024-03-20', 'pending', 300.00);

        $result = LaravelMetrics::query($this->db())
            ->average('amount')
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEqualsWithDelta(200.00, $result, 0.001);
        $this->assertIsFloat($result);
    }

    public function test_average_with_decimal_result(): void
    {
        $this->insert('2024-03-10', 'pending', 100.00);
        $this->insert('2024-03-15', 'pending', 200.00);

        $result = LaravelMetrics::query($this->db())
            ->average('amount')
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEqualsWithDelta(150.00, $result, 0.001);
    }

    public function test_max(): void
    {
        $this->insert('2024-03-10', 'pending', 100.00);
        $this->insert('2024-03-15', 'pending', 500.00);
        $this->insert('2024-03-20', 'pending', 300.00);

        $result = LaravelMetrics::query($this->db())
            ->max('amount')
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEqualsWithDelta(500.00, $result, 0.001);
    }

    public function test_min(): void
    {
        $this->insert('2024-03-10', 'pending', 100.00);
        $this->insert('2024-03-15', 'pending', 500.00);
        $this->insert('2024-03-20', 'pending', 300.00);

        $result = LaravelMetrics::query($this->db())
            ->min('amount')
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEqualsWithDelta(100.00, $result, 0.001);
    }

    public function test_count_between_dates(): void
    {
        $this->insert('2024-01-05');
        $this->insert('2024-01-10');
        $this->insert('2024-01-15');
        $this->insert('2024-01-20'); // outside range

        $result = LaravelMetrics::query($this->db())
            ->countBetween(['2024-01-01', '2024-01-16'])
            ->metrics();

        $this->assertEquals(3, $result);
    }

    public function test_sum_between_dates(): void
    {
        $this->insert('2024-01-05', 'pending', 100.00);
        $this->insert('2024-01-10', 'pending', 200.00);
        $this->insert('2024-01-20', 'pending', 999.00); // excluded

        $result = LaravelMetrics::query($this->db())
            ->sumBetween(['2024-01-01', '2024-01-15'], 'amount')
            ->metrics();

        $this->assertEqualsWithDelta(300.00, $result, 0.001);
    }

    public function test_average_between_dates(): void
    {
        $this->insert('2024-01-05', 'pending', 100.00);
        $this->insert('2024-01-10', 'pending', 300.00);
        $this->insert('2024-01-20', 'pending', 999.00); // excluded

        $result = LaravelMetrics::query($this->db())
            ->averageBetween(['2024-01-01', '2024-01-15'], 'amount')
            ->metrics();

        $this->assertEqualsWithDelta(200.00, $result, 0.001);
    }

    public function test_count_from_date(): void
    {
        $this->insert('2024-01-05');
        $this->insert('2024-06-10');
        $this->insert('2024-12-15');
        $this->insert('2023-12-31'); // before from date

        $result = LaravelMetrics::query($this->db())
            ->countFrom('2024-01-01')
            ->metrics();

        $this->assertEquals(3, $result);
    }

    public function test_by_today_counts_only_todays_records(): void
    {
        $today = date('Y-m-d');
        $this->insert($today.' 08:00:00');
        $this->insert($today.' 12:00:00');
        $this->insert('2020-01-01'); // not today

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byToday()
            ->metrics();

        $this->assertEquals(2, $result);
    }

    public function test_count_with_custom_date_column(): void
    {
        // orders table uses created_at, test that dateColumn() override is accepted
        $this->insert('2024-05-10');
        $this->insert('2024-05-20');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(5)
            ->dateColumn('created_at')
            ->metrics();

        $this->assertEquals(2, $result);
    }

    public function test_count_week_period(): void
    {
        // Jan 15 2024 is in week 3 (strftime %W returns 02 for the 3rd week)
        $this->insert('2024-01-15');
        $this->insert('2024-01-16');
        $this->insert('2024-01-22'); // different week

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byWeek(1)
            ->forYear(2024)
            ->forMonth(1)
            ->forWeek(3)
            ->metrics();

        $this->assertEquals(2, $result);
    }
}
