<?php

namespace Eliseekn\LaravelMetrics;

use Illuminate\Support\Facades\Facade;

class LaravelMetricsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-metrics';
    }
}
