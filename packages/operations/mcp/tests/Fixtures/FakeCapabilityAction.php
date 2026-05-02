<?php

declare(strict_types=1);

namespace Capell\Mcp\Tests\Fixtures;

use Capell\Mcp\Contracts\CapellMcpCapabilityAction;
use Capell\Mcp\Data\CapabilityInvocationData;
use Capell\Mcp\Data\CapabilityResultData;

final class FakeCapabilityAction implements CapellMcpCapabilityAction
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
