<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Base exception class for the Tipsy framework
 * 
 * Provides framework-specific exception handling with PHP 8.3+ features
 */
class Exception extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}