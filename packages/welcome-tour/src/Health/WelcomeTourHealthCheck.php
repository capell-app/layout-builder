<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class WelcomeTourHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
