<?php

declare(strict_types=1);

namespace Capell\Migrator\Actions;

use Capell\Migrator\Models\ImportRollbackReport;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Services\Import\ImportExecutionReport;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static ImportRollbackReport run(ImportSession $session, ImportExecutionReport $report)
 */
final class CreateImportRollbackReportAction
{
    use AsAction;

    public function handle(ImportSession $session, ImportExecutionReport $report): ImportRollbackReport
    {
        return ImportRollbackReport::query()->create([
            'import_session_id' => $session->getKey(),
            'user_id' => $session->user_id,
            'source_filename' => $session->source_filename,
            'source_package_checksum' => $session->source_package_checksum,
            'created_models' => $report->createdModels(),
            'summary' => $report->toArray(),
            'manual_instructions' => $this->instructionsFor($report),
            'executed_at' => $session->executed_at ?? now(),
        ]);
    }

    private function instructionsFor(ImportExecutionReport $report): string
    {
        if ($report->createdModels() === []) {
            return 'No records were created. Review the import session summary before taking further action.';
        }

        return 'To roll back this import manually, review each created model listed in this report, remove any records that were not edited after import, then clear related URLs and media assignments recorded in the summary.';
    }
}
