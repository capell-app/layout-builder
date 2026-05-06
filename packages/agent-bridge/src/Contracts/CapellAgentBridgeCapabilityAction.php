<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Contracts;

use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Data\CapabilityResultData;

interface CapellAgentBridgeCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData;

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData;
}
