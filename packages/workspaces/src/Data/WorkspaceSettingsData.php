<?php

declare(strict_types=1);

namespace Capell\Workspaces\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class WorkspaceSettingsData extends Data
{
    public function __construct(
        public int $requiredApprovalLevels = 2,
    ) {}
}
