<?php

declare(strict_types=1);

namespace Capell\Mcp\Contracts;

use Capell\Mcp\Data\CapabilityInvocationData;
use Capell\Mcp\Data\CapabilityResultData;

interface CapellMcpCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData;

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData;
}
