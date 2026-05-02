<?php

declare(strict_types=1);

namespace Capell\Workspaces\Exceptions;

use Capell\Workspaces\Checks\PublishCheckResult;
use RuntimeException;

class PublishBlockedByChecksException extends RuntimeException
{
    /** @param array<int, PublishCheckResult> $checkResults */
    public function __construct(public readonly array $checkResults)
    {
        parent::__construct('Publish blocked by failing checks: ' . implode(', ', array_map(
            fn (PublishCheckResult $result): string => $result->identifier,
            array_filter($checkResults, fn (PublishCheckResult $result): bool => $result->isError() && ! $result->isClean()),
        )));
    }
}
