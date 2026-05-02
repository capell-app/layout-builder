<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Site;

use Capell\Mcp\Actions\ConfirmMcpCapabilityAction;
use Capell\Mcp\Data\AuthenticatedMcpClientData;
use Capell\Mcp\Models\CapellMcpToken;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

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

    public function handle(Request $request, AuthenticatedMcpClientData $client, CapellMcpToken $token): ResponseFactory
    {
        $data = $request->validate([
            'confirmationToken' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        $result = ConfirmMcpCapabilityAction::run(
            confirmationToken: (string) $data['confirmationToken'],
            payload: $data['payload'],
            client: $client,
            token: $token,
            user: $request->user(),
        );

        return Response::structured($result);
    }
}
