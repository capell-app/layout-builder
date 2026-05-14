<?php

declare(strict_types=1);

namespace Capell\Api\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class ApiHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
