<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Actions\Cache;

use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityAction;
use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Data\CapabilityResultData;
use Illuminate\Support\Facades\Artisan;

final class ClearCapellCacheCapabilityAction implements CapellAgentBridgeCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        return new CapabilityResultData(
            ok: true,
            message: 'Capell cache clear commands will be run if they are registered in this application.',
            data: [
                'commands' => $this->availableCommands(),
            ],
        );
    }

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData
    {
        $results = [];

        foreach ($this->availableCommands() as $command) {
            Artisan::call($command);
            $results[$command] = trim(Artisan::output());
        }

        return new CapabilityResultData(
            ok: true,
            message: 'Available Capell cache clear commands have been run.',
            data: [
                'results' => $results,
            ],
        );
    }

    /** @return array<int, string> */
    private function availableCommands(): array
    {
        $commands = [
            'cache:clear',
            'config:clear',
            'view:clear',
            'capell:clear-components-cache',
            'capell:admin:clear-cache',
            'capell:admin:clear-configurators-cache',
        ];

        $available = array_keys(Artisan::all());

        return array_values(array_intersect($commands, $available));
    }
}
