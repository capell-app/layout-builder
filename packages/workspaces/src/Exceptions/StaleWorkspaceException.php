<?php

declare(strict_types=1);

namespace Capell\Workspaces\Exceptions;

use Capell\Workspaces\Models\Workspace;
use RuntimeException;

/**
 * Thrown when publish is attempted on a workspace whose `base_version_id` is
 * behind the current live version. The workspace must be rebased via the
 * {@see Rebaser} before it can publish.
 */
class StaleWorkspaceException extends RuntimeException
{
    public function __construct(
        public readonly ?Workspace $workspace,
        public readonly int $currentLiveVersionId,
    ) {
        $message = $workspace instanceof Workspace
            ? sprintf(
                'Workspace #%d "%s" is based on version %d but live is now at version %d. Rebase before publishing.',
                $workspace->id,
                $workspace->name,
                (int) $workspace->base_version_id,
                $currentLiveVersionId,
            )
            : sprintf(
                'Live version changed during rollback; expected previous live but found version %d. Retry the rollback.',
                $currentLiveVersionId,
            );

        parent::__construct($message);
    }
}
