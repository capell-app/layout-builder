<?php

declare(strict_types=1);

namespace Capell\SeoTools\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when the OpenAI API circuit breaker is open and requests are blocked.
 */
class OpenAICircuitBreakerOpenException extends RuntimeException
{
    public function __construct(string $message = 'OpenAI API circuit breaker is open', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
