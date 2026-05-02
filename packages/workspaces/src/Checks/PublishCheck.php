<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

use Capell\Workspaces\Models\Workspace;

/**
 * A pluggable validator run against a workspace before publish. Checks must
 * be idempotent and read-only — they never mutate workspace state.
 */
interface PublishCheck
{
    public function identifier(): string;

    public function label(): string;

    public function run(Workspace $workspace): PublishCheckResult;
}
