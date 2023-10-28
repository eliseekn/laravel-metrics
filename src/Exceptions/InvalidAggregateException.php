<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Exceptions;

use Exception;

/**
 * This exception occurs when date format is invalid
 */
class InvalidAggregateException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid aggregate value. Valid aggregate is count, sum, max, min or avg');
    }
}
