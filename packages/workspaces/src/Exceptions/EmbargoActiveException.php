<?php

declare(strict_types=1);

namespace Capell\Workspaces\Exceptions;

use Capell\Workspaces\Models\Workspace;
use Carbon\CarbonInterface;
use RuntimeException;

final class EmbargoActiveException extends RuntimeException
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly CarbonInterface $embargoUntil,
    ) {
        parent::__construct(sprintf(
            'Workspace #%d cannot be published before its embargo date: %s.',
            $workspace->id,
            $embargoUntil->toDateTimeString(),
        ));
    }
}
