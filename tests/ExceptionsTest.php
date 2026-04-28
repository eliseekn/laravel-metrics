<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Eliseekn\LaravelMetrics\Exceptions\InvalidAggregateException;
use Eliseekn\LaravelMetrics\Exceptions\InvalidDateFormatException;
use Eliseekn\LaravelMetrics\Exceptions\InvalidPeriodException;
use Eliseekn\LaravelMetrics\Exceptions\InvalidVariationsCountException;
use Eliseekn\LaravelMetrics\LaravelMetrics;

class ExceptionsTest extends TestCase
{
    public function test_invalid_period_throws_exception(): void
    {
        $this->expectException(InvalidPeriodException::class);

        LaravelMetrics::query($this->db())
            ->count()
            ->byDay() // valid call to init
            ->metricsWithVariations(1, 'quarterly'); // invalid period
    }

    public function test_invalid_period_via_by_method(): void
    {
        $this->expectException(InvalidPeriodException::class);
        $this->expectExceptionMessage('Invalid period');

        // Access protected by() through a subclass or via reflection isn't ideal;
        // use metricsWithVariations which also validates the period
        LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(1)
            ->metricsWithVariations(1, 'invalid_period');
    }

    public function test_invalid_aggregate_throws_exception(): void
    {
        $this->expectException(InvalidAggregateException::class);

        // Directly calling the protected aggregate() method isn't possible,
        // but metricsWithVariations internally uses it — test via invalid combination
        // We test it by bypassing validation using reflection
        $metrics = LaravelMetrics::query($this->db());

        $reflection = new \ReflectionClass($metrics);
        $method = $reflection->getMethod('aggregate');
        $method->invoke($metrics, 'median', 'amount');
    }

    public function test_invalid_date_format_throws_exception(): void
    {
        $this->expectException(InvalidDateFormatException::class);
        $this->expectExceptionMessage('Invalid date format');

        LaravelMetrics::query($this->db())
            ->count()
            ->between('2024/01/01', '2024/12/31'); // slashes instead of dashes
    }

    public function test_invalid_date_format_with_wrong_format(): void
    {
        $this->expectException(InvalidDateFormatException::class);

        LaravelMetrics::query($this->db())
            ->count()
            ->between('01-01-2024', '31-12-2024'); // DD-MM-YYYY instead of YYYY-MM-DD
    }

    public function test_invalid_date_format_with_incomplete_date(): void
    {
        $this->expectException(InvalidDateFormatException::class);

        LaravelMetrics::query($this->db())
            ->count()
            ->between('2024-13-01', '2024-12-31'); // month 13 doesn't exist
    }

    public function test_invalid_variations_count_when_zero(): void
    {
        $this->expectException(InvalidVariationsCountException::class);

        LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(3)
            ->metricsWithVariations(0, 'month');
    }

    public function test_invalid_variations_count_when_negative(): void
    {
        $this->expectException(InvalidVariationsCountException::class);

        LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(3)
            ->metricsWithVariations(-5, 'month');
    }

    public function test_valid_periods_do_not_throw(): void
    {
        $this->expectNotToPerformAssertions();

        foreach (['day', 'week', 'month', 'year'] as $period) {
            LaravelMetrics::query($this->db())
                ->count()
                ->byMonth(1)
                ->forYear(2024)->forMonth(1)
                ->metricsWithVariations(1, $period);
        }
    }

    public function test_valid_date_format_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();

        LaravelMetrics::query($this->db())
            ->count()
            ->between('2024-01-01', '2024-12-31')
            ->metrics();
    }
}
