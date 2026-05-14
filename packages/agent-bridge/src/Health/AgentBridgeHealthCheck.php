<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class AgentBridgeHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
