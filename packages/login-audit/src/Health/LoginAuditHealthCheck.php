<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class LoginAuditHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
