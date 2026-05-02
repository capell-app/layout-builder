<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Restore;

use Capell\Backup\Exceptions\NotImplementedException;

/**
 * H6 placeholder. Drives full-environment restore from an encrypted
 * backup archive (database + media + settings). BackupRestore model
 * will track lifecycle analogous to ImportSession.
 */
final class RestoreService
{
    public function restore(): never
    {
        throw NotImplementedException::forPhase('H6', self::class);
    }
}
