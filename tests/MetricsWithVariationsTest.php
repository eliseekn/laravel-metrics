<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Carbon\Carbon;
use Eliseekn\LaravelMetrics\LaravelMetrics;

class MetricsWithVariationsTest extends TestCase
{
    public function test_returns_count_and_variation_keys(): void
    {
        $this->insert(Carbon::now()->format('Y-m-01'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month');

        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('variation', $result);
    }

    public function test_increase_variation(): void
    {
        // Current month: 3 records
        $this->insert(Carbon::now()->format('Y-m-01'));
        $this->insert(Carbon::now()->format('Y-m-01'));
        $this->insert(Carbon::now()->format('Y-m-01'));
        // Previous month: 1 record
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month');

        $this->assertEquals(3, $result['count']);
        $this->assertEquals('increase', $result['variation']['type']);
        $this->assertEquals(2, $result['variation']['value']); // 3 - 1 = 2
    }

    public function test_decrease_variation(): void
    {
        // Current month: 1 record
        $this->insert(Carbon::now()->format('Y-m-01'));
        // Previous month: 3 records
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month');

        $this->assertEquals(1, $result['count']);
        $this->assertEquals('decrease', $result['variation']['type']);
        $this->assertEquals(2, $result['variation']['value']); // abs(1 - 3)
    }

    public function test_no_variation_returns_empty_variation_array(): void
    {
        $this->insert(Carbon::now()->format('Y-m-01'));
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month');

        $this->assertEquals(1, $result['count']);
        $this->assertEquals([], $result['variation']);
    }

    public function test_increase_variation_in_percent(): void
    {
        // Current: 2, Previous: 1 → 100% increase
        $this->insert(Carbon::now()->format('Y-m-01'));
        $this->insert(Carbon::now()->format('Y-m-01'));
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month', true);

        $this->assertEquals('increase', $result['variation']['type']);
        $this->assertStringEndsWith('%', (string) $result['variation']['value']);
        $this->assertStringContainsString('100', (string) $result['variation']['value']);
    }

    public function test_variation_with_year_period(): void
    {
        // Current year: 2 records
        $this->insert(Carbon::now()->format('Y-03-01'));
        $this->insert(Carbon::now()->format('Y-06-10'));
        // Previous year: 1 record
        $this->insert(Carbon::now()->subYear()->format('Y-06-10'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byYear(1)
            ->metricsWithVariations(1, 'year');

        $this->assertEquals(2, $result['count']);
        $this->assertEquals('increase', $result['variation']['type']);
        $this->assertEquals(1, $result['variation']['value']);
    }

    public function test_variation_count_is_zero_for_current_period_with_no_data(): void
    {
        // Only previous period has data
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'));

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month');

        $this->assertEquals(0, $result['count']);
        $this->assertEquals('decrease', $result['variation']['type']);
        $this->assertEquals(1, $result['variation']['value']);
    }

    public function test_variation_preserves_original_builder_filters(): void
    {
        // Current month: 2 pending, 1 delivered
        $this->insert(Carbon::now()->format('Y-m-01'), 'pending');
        $this->insert(Carbon::now()->format('Y-m-01'), 'pending');
        $this->insert(Carbon::now()->format('Y-m-01'), 'delivered');
        // Previous month: 1 pending
        $this->insert(Carbon::now()->subMonth()->format('Y-m-01'), 'pending');

        // Filter to only 'pending' orders — the variation query must inherit this filter
        $builder = $this->db()->where('status', 'pending');

        $result = LaravelMetrics::query($builder)
            ->count()
            ->byMonth(1)
            ->metricsWithVariations(1, 'month');

        // Current: 2 pending; Previous: 1 pending
        $this->assertEquals(2, $result['count']);
        $this->assertEquals('increase', $result['variation']['type']);
        $this->assertEquals(1, $result['variation']['value']);
    }
}
