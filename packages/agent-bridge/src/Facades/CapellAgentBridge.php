<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Facades;

use Capell\AgentBridge\Data\CapabilityData;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(CapabilityData $capability)
 * @method static CapabilityData get(string $key)
 * @method static bool has(string $key)
 *
 * @see CapellAgentBridgeCapabilityRegistry
 */
final class CapellAgentBridge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CapellAgentBridgeCapabilityRegistry::class;
    }
}
