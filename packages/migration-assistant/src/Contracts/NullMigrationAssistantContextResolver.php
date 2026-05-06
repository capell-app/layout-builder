<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Contracts;

use Closure;

final class NullMigrationAssistantContextResolver implements MigrationAssistantContextResolver
{
    public function wrap(Closure $callback): mixed
    {
        return $callback();
    }
}
