<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Exceptions;

use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use RuntimeException;

class InvalidScheduleException extends RuntimeException
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly CarbonImmutable $requestedAt,
        string $reason,
    ) {
        parent::__construct(sprintf(
            'Cannot schedule workspace #%d for %s: %s',
            $workspace->id,
            $requestedAt->toDateTimeString(),
            $reason,
        ));
    }

    public static function mustBeInFuture(Workspace $workspace, CarbonImmutable $requestedAt): self
    {
        return new self($workspace, $requestedAt, 'publish_at must be in the future.');
    }

    public static function wrongStatus(Workspace $workspace, CarbonImmutable $requestedAt): self
    {
        return new self($workspace, $requestedAt, sprintf(
            'workspace must be approved to be scheduled (current status: %s).',
            $workspace->status->value,
        ));
    }
}
