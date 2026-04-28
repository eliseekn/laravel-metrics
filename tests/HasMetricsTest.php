<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Eliseekn\LaravelMetrics\LaravelMetrics;
use Eliseekn\LaravelMetrics\Tests\Models\Order;

class HasMetricsTest extends TestCase
{
    public function test_has_metrics_trait_returns_laravel_metrics_instance(): void
    {
        $result = Order::metrics();

        $this->assertInstanceOf(LaravelMetrics::class, $result);
    }

    public function test_has_metrics_count_via_model(): void
    {
        $this->insert('2024-03-10');
        $this->insert('2024-03-15');
        $this->insert('2024-04-01'); // excluded

        $result = Order::metrics()
            ->count()
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEquals(2, $result);
    }

    public function test_has_metrics_trends_via_model(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-03-15');

        $result = Order::metrics()
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertContains('January', $result['labels']);
        $this->assertContains('March', $result['labels']);
    }

    public function test_has_metrics_sum_via_model(): void
    {
        $this->insert('2024-03-10', 'pending', 150.00);
        $this->insert('2024-03-15', 'pending', 250.00);

        $result = Order::metrics()
            ->sum('amount')
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEqualsWithDelta(400.00, $result, 0.001);
    }

    public function test_has_metrics_with_fill_missing_data(): void
    {
        $this->insert('2024-06-10');

        $result = Order::metrics()
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->fillMissingData()
            ->trends();

        $this->assertCount(12, $result['labels']);
    }

    public function test_has_metrics_returns_zero_for_empty_table(): void
    {
        $result = Order::metrics()
            ->count()
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(1)
            ->metrics();

        $this->assertEquals(0, $result);
    }

    public function test_filtered_query_via_laravel_metrics_query(): void
    {
        // HasMetrics::metrics() always starts a clean query on the model.
        // For filtered queries, pass the builder directly to LaravelMetrics::query().
        $this->insert('2024-03-10', 'pending');
        $this->insert('2024-03-15', 'delivered');
        $this->insert('2024-03-20', 'pending');

        $result = LaravelMetrics::query(Order::where('status', 'pending'))
            ->count()
            ->byMonth(1)
            ->forYear(2024)
            ->forMonth(3)
            ->metrics();

        $this->assertEquals(2, $result);
    }
}
