<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Actions;

use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Jobs\ExecuteImportPlanJob;
use Capell\MigrationAssistant\Models\ImportSession;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

/**
 * Re-dispatches the execute plan for a failed import session. Used by
 * the Recovery Center ImportSessionResource retry header action (§6.8).
 *
 * Guard rails: the session must be in the `Failed` state, must still
 * have the resolution_map / decisions, and the source archive must
 * still be present on the configured migration-assistant disk.
 */
final class RetryImportSessionAction
{
    use AsAction;

    public static function canRetry(ImportSession $session): bool
    {
        if ($session->status !== ImportSessionStatus::Failed) {
            return false;
        }

        if ($session->resolution_map === null || $session->page_decisions === null || $session->relation_decisions === null) {
            return false;
        }

        $archivePath = (string) $session->source_package_path;

        return $archivePath !== '' && self::archiveDisk()->exists($archivePath);
    }

    public function handle(ImportSession $session): ImportSession
    {
        throw_if($session->status !== ImportSessionStatus::Failed, RuntimeException::class, 'Only failed sessions can be retried.');

        throw_if($session->resolution_map === null || $session->page_decisions === null || $session->relation_decisions === null, RuntimeException::class, 'Session is missing resolution data and cannot be retried.');

        $archivePath = (string) $session->source_package_path;
        throw_if($archivePath === '' || ! self::archiveDisk()->exists($archivePath), RuntimeException::class, 'Source archive is no longer present on disk.');

        $session->forceFill([
            'status' => ImportSessionStatus::Queued,
            'failure_reason' => null,
        ])->save();

        dispatch(new ExecuteImportPlanJob((int) $session->getKey()));

        return $session->refresh();
    }

    private static function archiveDisk(): Filesystem
    {
        $diskName = config('migration-assistant.disk', 'local');

        return Storage::disk(is_string($diskName) ? $diskName : 'local');
    }
}
