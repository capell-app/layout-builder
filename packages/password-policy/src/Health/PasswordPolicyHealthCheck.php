<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class PasswordPolicyHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
