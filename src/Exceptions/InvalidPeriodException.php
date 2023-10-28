<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Exceptions;

use Exception;

/**
 * This exception occurs when date format is invalid
 */
class InvalidPeriodException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid period value. Valid period is day, week, month or year');
    }
}
