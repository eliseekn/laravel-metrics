<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Exceptions;

use Exception;

/**
 * This exception occurs when withVariationsCount parameter is equal 0
 */
class InvalidVariationsCountException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid withVariationsCount value. withVariationsCount value should be more than 0');
    }
}
