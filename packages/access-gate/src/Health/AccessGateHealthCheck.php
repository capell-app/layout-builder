<?php

declare(strict_types=1);

namespace Capell\AccessGate\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class AccessGateHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
