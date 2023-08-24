<?php
declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Enums;

enum Aggregate: string
{
    case COUNT = 'count';
    case AVERAGE = 'avg';
    case SUM = 'sum';
    case MAX = 'max';
    case MIN = 'min';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}