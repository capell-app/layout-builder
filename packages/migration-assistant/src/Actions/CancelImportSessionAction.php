<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Actions;

use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Models\ImportSession;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

/**
 * Transitions an import session to the `Abandoned` terminal state. Used
 * by the Recovery Center ImportSessionResource cancel header action (§6.8).
 *
 * Cancelling a `Running` session is intentionally out of scope — that
 * requires cooperative cancellation on the worker side.
 */
final class CancelImportSessionAction
{
    use AsAction;

    public static function isCancellable(ImportSession $session): bool
    {
        return match ($session->status) {
            ImportSessionStatus::Draft,
            ImportSessionStatus::Parsed,
            ImportSessionStatus::Mapped,
            ImportSessionStatus::Validated,
            ImportSessionStatus::Queued => true,
            default => false,
        };
    }

    public function handle(ImportSession $session): ImportSession
    {
        if (! self::isCancellable($session)) {
            throw new RuntimeException(
                'Import session cannot be cancelled from status ' . $session->status->value,
            );
        }

        $session->forceFill([
            'status' => ImportSessionStatus::Abandoned,
        ])->save();

        return $session->refresh();
    }
}
