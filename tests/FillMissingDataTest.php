<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests;

use Eliseekn\LaravelMetrics\LaravelMetrics;

class FillMissingDataTest extends TestCase
{
    public function test_fill_missing_data_includes_all_months_of_year(): void
    {
        $this->insert('2024-01-10');
        $this->insert('2024-12-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->fillMissingData()
            ->trends();

        // All 12 months should be present
        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['data']);
        $this->assertContains('January', $result['labels']);
        $this->assertContains('December', $result['labels']);
    }

    public function test_fill_missing_data_sets_zero_for_empty_months(): void
    {
        $this->insert('2024-01-10');
        // February has no data

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->fillMissingData()
            ->trends();

        $febIndex = array_search('February', $result['labels']);
        $this->assertNotFalse($febIndex);
        $this->assertEquals(0, $result['data'][$febIndex]);

        $janIndex = array_search('January', $result['labels']);
        $this->assertEquals(1, $result['data'][$janIndex]);
    }

    public function test_fill_missing_data_with_custom_default_value(): void
    {
        $this->insert('2024-01-10');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2024)
            ->fillMissingData(missingDataValue: -1)
            ->trends();

        $febIndex = array_search('February', $result['labels']);
        $this->assertEquals(-1, $result['data'][$febIndex]);
    }

    public function test_fill_missing_data_for_past_year_includes_all_12_months(): void
    {
        // 2023 is a past year — previously a bug caused it to stop at current month
        $this->insert('2023-01-10');
        $this->insert('2023-06-15');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth()
            ->forYear(2023)
            ->fillMissingData()
            ->trends();

        $this->assertCount(12, $result['labels']);
        $this->assertContains('December', $result['labels']);
    }

    public function test_fill_missing_data_for_between_period(): void
    {
        $this->insert('2024-01-05');
        // Jan 8, 10 have no data
        $this->insert('2024-01-12');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->between('2024-01-01', '2024-01-15')
            ->fillMissingData()
            ->trends();

        // All days from Jan 1 to Jan 15 should be present
        $this->assertCount(15, $result['labels']);
        $this->assertContains('2024-01-01', $result['labels']);
        $this->assertContains('2024-01-15', $result['labels']);
    }

    public function test_fill_missing_data_between_missing_days_have_zero(): void
    {
        $this->insert('2024-01-05');
        $this->insert('2024-01-10');

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->between('2024-01-01', '2024-01-10')
            ->fillMissingData()
            ->trends();

        $idx01 = array_search('2024-01-01', $result['labels']);
        $idx05 = array_search('2024-01-05', $result['labels']);
        $idx10 = array_search('2024-01-10', $result['labels']);

        $this->assertEquals(0, $result['data'][$idx01]);
        $this->assertEquals(1, $result['data'][$idx05]);
        $this->assertEquals(1, $result['data'][$idx10]);
    }

    public function test_fill_missing_data_for_days_in_month(): void
    {
        // byDay() groups by day-of-week names; getDaysData() fills from start of week
        $this->insert('2024-01-15'); // Monday

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byDay()
            ->forYear(2024)->forMonth(1)
            ->fillMissingData()
            ->trends();

        $this->assertContains('Monday', $result['labels']);
        $monIndex = array_search('Monday', $result['labels']);
        $this->assertEquals(1, $result['data'][$monIndex]);
    }

    public function test_fill_missing_data_for_year_period(): void
    {
        $this->insert('2022-06-10');
        $this->insert('2024-06-10');
        // 2023 has no data

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byYear(3)
            ->forYear(2024)
            ->fillMissingData()
            ->trends();

        $this->assertContains(2022, $result['labels']);
        $this->assertContains(2023, $result['labels']);
        $this->assertContains(2024, $result['labels']);

        $idx2023 = array_search(2023, $result['labels']);
        $this->assertEquals(0, $result['data'][$idx2023]);
    }

    public function test_fill_missing_data_with_explicit_labels(): void
    {
        $this->insert('2024-01-10', 'pending');
        // 'delivered' has no records

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(1)
            ->labelColumn('status')
            ->fillMissingData(missingDataLabels: ['pending', 'delivered', 'cancelled'])
            ->trends();

        $this->assertContains('pending', $result['labels']);
        $this->assertContains('delivered', $result['labels']);
        $this->assertContains('cancelled', $result['labels']);

        $deliveredIndex = array_search('delivered', $result['labels']);
        $this->assertEquals(0, $result['data'][$deliveredIndex]);
    }

    public function test_fill_missing_data_auto_discovers_label_column_values(): void
    {
        $this->insert('2024-01-10', 'pending');
        $this->insert('2024-01-15', 'delivered');
        // Both statuses exist; January data only has 'pending' in month scope

        $result = LaravelMetrics::query($this->db())
            ->count()
            ->byMonth(1)
            ->forYear(2024)->forMonth(1)
            ->labelColumn('status')
            ->fillMissingData()
            ->trends();

        // Auto-discovers 'pending' and 'delivered' from the table
        $this->assertContains('pending', $result['labels']);
        $this->assertContains('delivered', $result['labels']);
    }
}
