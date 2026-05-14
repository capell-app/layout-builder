<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class DiagnosticsHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
