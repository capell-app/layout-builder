<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Agency\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class ThemeAgencyHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
