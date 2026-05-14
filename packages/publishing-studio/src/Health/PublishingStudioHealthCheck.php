<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class PublishingStudioHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
