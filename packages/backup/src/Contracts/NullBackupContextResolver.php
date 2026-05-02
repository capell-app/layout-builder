<?php

declare(strict_types=1);

namespace Capell\Backup\Contracts;

use Closure;

final class NullBackupContextResolver implements BackupContextResolver
{
    public function wrap(Closure $callback): mixed
    {
        return $callback();
    }
}
