<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Site;

use Capell\Mcp\Actions\InvokeMcpCapabilityPreviewAction;
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

    public function handle(Request $request, AuthenticatedMcpClientData $client, CapellMcpToken $token): ResponseFactory
    {
        $data = $request->validate([
            'capability' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        $result = InvokeMcpCapabilityPreviewAction::run(
            capabilityKey: (string) $data['capability'],
            payload: $data['payload'],
            client: $client,
            token: $token,
            user: $request->user(),
        );

        return Response::structured($result);
    }
}
