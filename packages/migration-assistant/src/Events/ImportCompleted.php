<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Events;

use Capell\MigrationAssistant\Models\ImportSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ExecuteImportPlanJob after a successful import.
 */
class ImportCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ImportSession $session) {}
}
