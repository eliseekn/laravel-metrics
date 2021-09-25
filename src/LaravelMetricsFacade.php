<?php

namespace Eliseekn\LaravelMetrics;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Eliseekn\LaravelMetrics\Skeleton\SkeletonClass
 */
class LaravelMetricsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-metrics';
    }
}
