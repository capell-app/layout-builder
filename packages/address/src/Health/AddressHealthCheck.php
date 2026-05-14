<?php

declare(strict_types=1);

namespace Capell\Address\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class AddressHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
