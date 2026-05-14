<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class FrontendAuthoringHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
