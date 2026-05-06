<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tests\Fixtures;

use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityAction;
use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Data\CapabilityResultData;

final class FakeCapabilityAction implements CapellAgentBridgeCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        return new CapabilityResultData(
            ok: true,
            message: 'Previewed fake capability.',
            data: [
                'payload' => $invocation->payload,
            ],
        );
    }

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData
    {
        return new CapabilityResultData(
            ok: true,
            message: 'Executed fake capability.',
            data: [
                'payload' => $invocation->payload,
            ],
        );
    }
}
