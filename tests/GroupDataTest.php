<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Eliseekn\LaravelMetrics\Enums\Aggregate;
use Eliseekn\LaravelMetrics\LaravelMetrics;

class GroupDataTest extends TestCase
{
    public function test_group_data_returns_labels_and_nested_data_keys(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-01-15', 'delivered');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(1)
            ->groupData(['pending', 'delivered'], Aggregate::COUNT->value)
            ->trends();

        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result['data']);
        $this->assertArrayHasKey('pending', $result['data']);
        $this->assertArrayHasKey('delivered', $result['data']);
    }

    public function test_group_data_total_contains_all_records(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-01-12', 'pending');
        $this->insert('2024-01-15', 'delivered');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(1)
            ->groupData(['pending', 'delivered'], Aggregate::COUNT->value)
            ->trends();

        // Total should be 3
        $this->assertEquals(3, array_sum($result['data']['total']));
    }

    public function test_group_data_per_label_counts_are_correct(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-01-12', 'pending');
        $this->insert('2024-01-15', 'delivered');
        $this->insert('2024-01-20', 'cancelled');

        // Use the status column + SUM aggregate: sum(orders.status = ?) yields 1 for
        // matches and 0 for non-matches, so array_sum gives the correct per-label count.
        $result = LaravelMetrics::query($this->db())
            ->countByMonth(column: 'status')
            ->forYear(2024)->forMonth(1)
            ->groupData(['pending', 'delivered', 'cancelled'], Aggregate::SUM->value)
            ->trends();

        $this->assertEquals(2, array_sum($result['data']['pending']));
        $this->assertEquals(1, array_sum($result['data']['delivered']));
        $this->assertEquals(1, array_sum($result['data']['cancelled']));
    }

    public function test_group_data_labels_match_trends_labels(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-03-15', 'delivered');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->groupData(['pending', 'delivered'], Aggregate::COUNT->value)
            ->trends();

        $this->assertContains('January', $result['labels']);
        $this->assertContains('March', $result['labels']);

        // Each data group should have same count as labels
        $this->assertCount(count($result['labels']), $result['data']['total']);
        $this->assertCount(count($result['labels']), $result['data']['pending']);
        $this->assertCount(count($result['labels']), $result['data']['delivered']);
    }

    public function test_group_data_with_fill_missing_data(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-03-15', 'delivered');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->groupData(['pending', 'delivered'], Aggregate::COUNT->value)
            ->fillMissingData()
            ->trends();

        // All 12 months should be present
        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['data']['total']);
        $this->assertCount(12, $result['data']['pending']);
        $this->assertCount(12, $result['data']['delivered']);
    }

    public function test_group_data_with_three_statuses(): void
    {
        $this->insert('2024-02-01', 'pending');
        $this->insert('2024-02-05', 'pending');
        $this->insert('2024-02-10', 'delivered');
        $this->insert('2024-02-15', 'cancelled');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(2)
            ->groupData(['pending', 'delivered', 'cancelled'], Aggregate::COUNT->value)
            ->trends();

        $this->assertArrayHasKey('pending', $result['data']);
        $this->assertArrayHasKey('delivered', $result['data']);
        $this->assertArrayHasKey('cancelled', $result['data']);
    }
}
