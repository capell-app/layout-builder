<?php

declare(strict_types=1);

namespace Capell\Workspaces\Activity;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * A single row rendered into the workspace activity feed. Shape is flat and
 * view-ready so the Filament widget doesn't need to peek at underlying
 * activity-log internals.
 */
class WorkspaceActivityEntry extends Data
{
    public function __construct(
        public int $workspaceId,
        public ?string $workspaceName,
        public string $description,
        public ?string $event,
        public ?int $causerId,
        public ?string $causerType,
        public CarbonImmutable $occurredAt,
    ) {}
}
