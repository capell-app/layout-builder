<?php

declare(strict_types=1);

namespace Capell\Migrator\Exceptions;

use RuntimeException;

/**
 * Thrown by sibling ImportSessionKind service stubs that exist only so
 * the enum/router isn't a trap. Each concrete implementation lands in
 * its own H3/H4/H6 phase; until then, calling the service surfaces this
 * exception with the tracking phase identifier.
 */
class NotImplementedException extends RuntimeException
{
    public static function forPhase(string $phase, string $component): self
    {
        return new self(sprintf('%s is not implemented yet — tracked in phase %s.', $component, $phase));
    }
}
