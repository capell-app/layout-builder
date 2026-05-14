<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class MigrationAssistantHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
