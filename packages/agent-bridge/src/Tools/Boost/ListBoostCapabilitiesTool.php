<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tools\Boost;

use Capell\AgentBridge\Data\CapabilityData;
use Capell\AgentBridge\Enums\CapabilityServerEnum;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Laravel\AgentBridge\Response;
use Laravel\AgentBridge\ResponseFactory;
use Laravel\AgentBridge\Server\Attributes\Description;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Title;
use Laravel\AgentBridge\Server\Tool;
use Laravel\AgentBridge\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-list-capabilities')]
#[Title('List Capell Capabilities')]
#[Description('List installed Capell Agent Bridge capabilities available through the Laravel Boost Agent Bridge server.')]
#[IsReadOnly]
final class ListBoostCapabilitiesTool extends Tool
{
    public function handle(CapellAgentBridgeCapabilityRegistry $registry): ResponseFactory
    {
        return Response::structured([
            'capabilities' => $registry
                ->visibleFor(CapabilityServerEnum::Site)
                ->map(fn (CapabilityData $capability): array => $capability->toPayload())
                ->all(),
            'confirmation' => 'Use the authenticated Capell Site Agent Bridge server for confirmed mutating operations.',
        ]);
    }
}
