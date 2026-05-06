<?php

declare(strict_types=1);

use Capell\AgentBridge\Tools\Boost\ListBoostCapabilitiesTool;
use Capell\AgentBridge\Tools\Boost\PreviewBoostCapabilityTool;

if (! class_exists('Laravel\\Boost\\AgentBridge\\Boost')) {
    eval('namespace Laravel\\Boost\\AgentBridge; class Boost {}');
}

it('registers Capell bridge tools with the Laravel Boost Agent Bridge server', function (): void {
    expect(config('boost.agent-bridge.tools.include'))
        ->toContain(ListBoostCapabilitiesTool::class)
        ->toContain(PreviewBoostCapabilityTool::class);
});
