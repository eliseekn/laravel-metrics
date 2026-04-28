<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Eliseekn\LaravelMetrics\LaravelMetrics;

class TrendsTest extends TestCase
{
    public function test_trends_returns_labels_and_data_keys(): void
    {
        $this->insert('2024-01-10');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['labels']);
        $this->assertIsArray($result['data']);
    }

    public function test_trends_by_month_labels_are_month_names(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-01-20');
        $this->insert('2024-03-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $this->assertContains('January', $result['labels']);
        $this->assertContains('March', $result['labels']);
        $this->assertNotContains('February', $result['labels']); // no data in Feb
        $this->assertCount(2, $result['labels']);
    }

    public function test_trends_by_month_data_counts_are_correct(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-01-20');
        $this->insert('2024-03-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $janIndex = array_search('January', $result['labels']);
        $marIndex = array_search('March', $result['labels']);

        $this->assertEquals(2, $result['data'][$janIndex]);
        $this->assertEquals(1, $result['data'][$marIndex]);
    }

    public function test_trends_by_year_labels_are_integers(): void
    {
        $this->insert('2022-01-10');
        $this->insert('2023-06-15');
        $this->insert('2024-12-01');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byYear()
            ->forYear(2024)
            ->trends();

        $this->assertContains(2022, $result['labels']);
        $this->assertContains(2023, $result['labels']);
        $this->assertContains(2024, $result['labels']);

        foreach ($result['labels'] as $label) {
            $this->assertIsInt($label);
        }
    }

    public function test_trends_by_day_labels_are_day_names(): void
    {
        // Jan 15 2024 = Monday, Jan 16 2024 = Tuesday
        $this->insert('2024-01-15');
        $this->insert('2024-01-16');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byDay()
            ->forYear(2024)
            ->forMonth(1)
            ->trends();

        $this->assertContains('Monday', $result['labels']);
        $this->assertContains('Tuesday', $result['labels']);
        $this->assertCount(2, $result['labels']);
    }

    public function test_trends_by_week_labels_are_week_strings(): void
    {
        $this->insert('2024-01-08'); // week 2
        $this->insert('2024-01-22'); // week 4

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byWeek()
            ->forYear(2024)
            ->forMonth(1)
            ->trends();

        foreach ($result['labels'] as $label) {
            $this->assertStringStartsWith('Week ', $label);
        }
        $this->assertCount(2, $result['labels']);
    }

    public function test_trends_between_dates(): void
    {
        $this->insert('2024-01-05');
        $this->insert('2024-01-10');
        $this->insert('2024-01-15');
        $this->insert('2024-01-20'); // outside range

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->between('2024-01-01', '2024-01-15')
            ->trends();

        $this->assertCount(3, $result['labels']);
        $this->assertEquals(['2024-01-05', '2024-01-10', '2024-01-15'], $result['labels']);
    }

    public function test_trends_in_percent_sums_to_100(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-01-20');
        $this->insert('2024-03-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends(true);

        $total = array_sum($result['data']);
        $this->assertEqualsWithDelta(100.0, $total, 0.01);
    }

    public function test_trends_in_percent_values_are_correct(): void
    {
        // 2 in Jan, 1 in March → 66.67% and 33.33%
        $this->insert('2024-01-10');
        $this->insert('2024-01-20');
        $this->insert('2024-03-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends(true);

        $janIndex = array_search('January', $result['labels']);
        $marIndex = array_search('March', $result['labels']);

        $this->assertEqualsWithDelta(66.67, $result['data'][$janIndex], 0.01);
        $this->assertEqualsWithDelta(33.33, $result['data'][$marIndex], 0.01);
    }

    public function test_trends_sum_by_month(): void
    {
        $this->insert('2024-01-10', 'pending', 100.00);
        $this->insert('2024-01-20', 'pending', 200.00);
        $this->insert('2024-03-15', 'pending', 150.00);

        $result = LaravelMetrics::query($this->db())
            ->sum('amount')
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $janIndex = array_search('January', $result['labels']);
        $marIndex = array_search('March', $result['labels']);

        $this->assertEqualsWithDelta(300.00, $result['data'][$janIndex], 0.001);
        $this->assertEqualsWithDelta(150.00, $result['data'][$marIndex], 0.001);
    }

    public function test_trends_with_label_column(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-01-15', 'delivered');
        $this->insert('2024-01-20', 'cancelled');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(12)
            ->forYear(2024)
            ->labelColumn('status')
            ->trends();

        $this->assertContains('pending', $result['labels']);
        $this->assertContains('delivered', $result['labels']);
        $this->assertContains('cancelled', $result['labels']);
    }

    public function test_trends_sum_of_delivered_orders_by_date(): void
    {
        $this->insert('2024-01-10', 'pending', 50.00);
        $this->insert('2024-01-15', 'delivered', 100.00);
        $this->insert('2024-02-05', 'delivered', 200.00);
        $this->insert('2024-03-20', 'cancelled', 999.00);
        $this->insert('2024-03-25', 'delivered', 300.00);

        $result = LaravelMetrics::query(
            $this->db()->where('status', 'delivered')
        )
            ->sum('amount')
            ->trends();

        $this->assertCount(3, $result['labels']);
        $this->assertEquals(['2024-01-15', '2024-02-05', '2024-03-25'], $result['labels']);
        $this->assertEqualsWithDelta(100.00, $result['data'][0], 0.001);
        $this->assertEqualsWithDelta(200.00, $result['data'][1], 0.001);
        $this->assertEqualsWithDelta(300.00, $result['data'][2], 0.001);
    }

    public function test_trends_returns_empty_when_no_data(): void
    {
        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $this->assertEquals([], $result['labels']);
        $this->assertEquals([], $result['data']);
    }

    public function test_trends_by_month_with_sum_returns_float_data(): void
    {
        $this->insert('2024-01-10', 'pending', 99.99);

        $result = LaravelMetrics::query($this->db())
            ->sum('amount')
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $this->assertIsFloat($result['data'][0]);
        $this->assertEqualsWithDelta(99.99, $result['data'][0], 0.001);
    }

    public function test_trends_between_with_group_by_month(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-02-15');
        $this->insert('2024-03-20');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->between('2024-01-01', '2024-03-31')
            ->groupByMonth()
            ->trends();

        $this->assertCount(3, $result['labels']);
    }

    public function test_trends_labels_and_data_have_same_length(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-03-15');
        $this->insert('2024-07-20');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->trends();

        $this->assertCount(count($result['labels']), $result['data']);
    }
}
