<?php
declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Exceptions;

use Exception;

/**
 * This exception occurs when date format is invalid
 */
class InvalidDateFormatException extends Exception
{
    public function __construct() {
        parent::__construct('Invalid date format. Valid date format is Y-m-d');
    }
}
