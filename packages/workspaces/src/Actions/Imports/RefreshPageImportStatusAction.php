<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions\Imports;

use Capell\Migrator\Enums\ImportSessionStatus;
use Capell\Migrator\Models\ImportSession;
use Capell\Workspaces\Data\Imports\PageImportStatusData;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static PageImportStatusData run(?int $sessionId, ?int $currentTargetWorkspaceId)
 */
final class RefreshPageImportStatusAction
{
    use AsAction;

    public function handle(?int $sessionId, ?int $currentTargetWorkspaceId): PageImportStatusData
    {
        if ($sessionId === null) {
            return new PageImportStatusData(step: 'executing');
        }

        $session = ImportSession::query()->find($sessionId);
        if (! $session instanceof ImportSession) {
            return new PageImportStatusData(step: 'executing');
        }

        $status = $session->status;
        $targetWorkspaceId = $session->workspace_id ?? $currentTargetWorkspaceId;
        $resultSummary = is_array($session->result_summary) ? $session->result_summary : [];

        if ($status === ImportSessionStatus::Completed) {
            return new PageImportStatusData(
                step: 'completed',
                sessionStatus: $status->value,
                resultSummary: $resultSummary,
                targetWorkspaceId: $targetWorkspaceId,
            );
        }

        if ($status === ImportSessionStatus::Failed) {
            return new PageImportStatusData(
                step: 'failed',
                sessionStatus: $status->value,
                resultSummary: $resultSummary,
                failureReason: $session->failure_reason,
                targetWorkspaceId: $targetWorkspaceId,
            );
        }

        return new PageImportStatusData(
            step: 'executing',
            sessionStatus: $status->value,
            resultSummary: $resultSummary,
            targetWorkspaceId: $targetWorkspaceId,
        );
    }
}
