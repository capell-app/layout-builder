<?php

declare(strict_types=1);

namespace Capell\Workspaces\Exceptions;

use RuntimeException;

class EntityNotInVersionException extends RuntimeException
{
    public static function missing(string $modelClass, string $entityUuid, int $versionId): self
    {
        return new self(sprintf(
            'Entity %s(uuid=%s) is not part of version #%d manifest.',
            $modelClass,
            $entityUuid,
            $versionId,
        ));
    }
}
