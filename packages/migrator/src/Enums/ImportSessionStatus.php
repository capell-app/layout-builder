<?php

declare(strict_types=1);

namespace Capell\Migrator\Enums;

enum ImportSessionStatus: string
{
    /** Created but nothing parsed yet — user is still on step 1 (upload). */
    case Draft = 'draft';

    /** Archive has been parsed and manifest validated. */
    case Parsed = 'parsed';

    /** User has reviewed and mapped incoming rows / decisions. */
    case Mapped = 'mapped';

    /** Dry-run validation has produced a report. */
    case Validated = 'validated';

    /** Execute dispatched to the migrator queue. */
    case Queued = 'queued';

    /** ExecuteImportPlanJob is currently writing to the target draft context. */
    case Running = 'running';

    /** Finished successfully. */
    case Completed = 'completed';

    /** Errored or halted and cannot proceed. */
    case Failed = 'failed';

    /** User cancelled the session before execution. */
    case Abandoned = 'abandoned';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Abandoned => true,
            default => false,
        };
    }
}
