<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tools\Site;

use Capell\AgentBridge\Actions\InvokeAgentBridgeCapabilityPreviewAction;
use Capell\AgentBridge\Data\AuthenticatedAgentBridgeClientData;
use Capell\AgentBridge\Models\CapellAgentBridgeToken;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\AgentBridge\Request;
use Laravel\AgentBridge\Response;
use Laravel\AgentBridge\ResponseFactory;
use Laravel\AgentBridge\Server\Attributes\Description;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Title;
use Laravel\AgentBridge\Server\Tool;
use Laravel\AgentBridge\Server\Tools\Annotations\IsDestructive;

#[Name('capell-site-run-capability')]
#[Title('Run Site Capability Preview')]
#[Description('Preview or directly execute a registered Capell site capability. Mutating capabilities return a confirmation token.')]
#[IsDestructive(false)]
final class RunSiteCapabilityTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'capability' => $schema->string()->description('Registered capability key.')->required(),
            'payload' => $schema->object()->description('Capability payload.')->required(),
        ];
    }

    public function handle(Request $request, AuthenticatedAgentBridgeClientData $client, CapellAgentBridgeToken $token): ResponseFactory
    {
        $data = $request->validate([
            'capability' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        $result = InvokeAgentBridgeCapabilityPreviewAction::run(
            capabilityKey: (string) $data['capability'],
            payload: $data['payload'],
            client: $client,
            token: $token,
            user: $request->user(),
        );

        return Response::structured($result);
    }
}
