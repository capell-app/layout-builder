<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tools\Site;

use Capell\AgentBridge\Actions\ConfirmAgentBridgeCapabilityAction;
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

#[Name('capell-site-confirm-capability')]
#[Title('Confirm Site Capability')]
#[Description('Execute a previously previewed mutating Capell site capability with the exact same payload.')]
#[IsDestructive]
final class ConfirmSiteCapabilityTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'confirmationToken' => $schema->string()->description('Confirmation token returned by capell-site-run-capability.')->required(),
            'payload' => $schema->object()->description('Exact payload used for the preview.')->required(),
        ];
    }

    public function handle(Request $request, AuthenticatedAgentBridgeClientData $client, CapellAgentBridgeToken $token): ResponseFactory
    {
        $data = $request->validate([
            'confirmationToken' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        $result = ConfirmAgentBridgeCapabilityAction::run(
            confirmationToken: (string) $data['confirmationToken'],
            payload: $data['payload'],
            client: $client,
            token: $token,
            user: $request->user(),
        );

        return Response::structured($result);
    }
}
