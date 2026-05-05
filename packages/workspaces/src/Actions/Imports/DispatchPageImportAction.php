<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions\Imports;

use Capell\Migrator\Enums\ImportSessionStatus;
use Capell\Migrator\Jobs\ExecuteImportPlanJob;
use Capell\Migrator\Models\ImportSession;
use Capell\Workspaces\Data\Imports\PageImportStatusData;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static PageImportStatusData run(?int $sessionId, array $validationSummary, string $confirmation, string $confirmationExpected)
 */
final class DispatchPageImportAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $validationSummary
     */
    public function handle(
        ?int $sessionId,
        array $validationSummary,
        string $confirmation,
        string $confirmationExpected,
    ): PageImportStatusData {
        if ($sessionId === null) {
            return new PageImportStatusData(step: 'validate');
        }

        $blockingErrors = $validationSummary['blocking_errors'] ?? [];
        if (is_array($blockingErrors) && $blockingErrors !== []) {
            return new PageImportStatusData(
                step: 'validate',
                notice: PageImportStatusData::NOTICE_SUMMARY_BLOCKING_ERRORS,
                noticeBody: implode(' / ', array_filter(
                    $blockingErrors,
                    is_string(...),
                )),
            );
        }

        if (! $this->confirmationMatches($confirmation, $confirmationExpected)) {
            return new PageImportStatusData(
                step: 'validate',
                notice: PageImportStatusData::NOTICE_CONFIRMATION_MISMATCH,
            );
        }

        $session = ImportSession::query()->find($sessionId);
        if (! $session instanceof ImportSession) {
            return new PageImportStatusData(step: 'validate');
        }

        $session->forceFill([
            'status' => ImportSessionStatus::Queued,
        ])->save();

        dispatch(new ExecuteImportPlanJob((int) $session->getKey()));

        return new PageImportStatusData(
            step: 'executing',
            sessionStatus: ImportSessionStatus::Queued->value,
            targetWorkspaceId: $session->workspace_id,
            notice: PageImportStatusData::NOTICE_IMPORT_QUEUED,
        );
    }

    private function confirmationMatches(string $confirmation, string $confirmationExpected): bool
    {
        if ($confirmationExpected === '') {
            return true;
        }

        return mb_strtolower(trim($confirmation)) === mb_strtolower(trim($confirmationExpected));
    }
}
