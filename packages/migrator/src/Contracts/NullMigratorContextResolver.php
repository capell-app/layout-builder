<?php

declare(strict_types=1);

namespace Capell\Migrator\Contracts;

use Closure;

final class NullMigratorContextResolver implements MigratorContextResolver
{
    public function wrap(Closure $callback): mixed
    {
        return $callback();
    }
}
