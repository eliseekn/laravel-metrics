<?php
declare(strict_types=1);

namespace Eliseekn\LaravelMetrics;

trait HasMetrics
{
    public static function metrics(): LaravelMetrics
    {
        $parent = get_called_class();
        return LaravelMetrics::query($parent::query());
    }
}
