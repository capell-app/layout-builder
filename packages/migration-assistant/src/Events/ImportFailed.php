<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Events;

use Capell\MigrationAssistant\Models\ImportSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ExecuteImportPlanJob when an import session terminates in
 * the Failed state (empty archive path, thrown exception, or a
 * non-success ImportReport).
 */
class ImportFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ImportSession $session,
        public string $reason,
    ) {}
}
