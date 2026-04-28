<?php

declare(strict_types=1);

namespace Capell\Plugins\Manifest\Exceptions;

use RuntimeException;

final class ManifestValidationException extends RuntimeException
{
    /** @var list<string> */
    private array $validationErrors;

    /**
     * @param  list<string>  $errors
     */
    public static function fromErrors(array $errors): self
    {
        $exception = new self('Plugin manifest validation failed: ' . implode('; ', $errors));
        $exception->validationErrors = $errors;

        return $exception;
    }

    /**
     * @return list<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
