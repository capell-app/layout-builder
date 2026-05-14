<?php

declare(strict_types=1);

namespace Capell\Hero\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class HeroHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
