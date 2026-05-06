<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Contracts;

use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;

interface CapellAgentBridgeCapabilityProvider
{
    public function registerCapabilities(CapellAgentBridgeCapabilityRegistry $registry): void;
}
