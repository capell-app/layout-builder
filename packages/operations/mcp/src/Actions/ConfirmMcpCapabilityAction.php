<?php

declare(strict_types=1);

namespace Capell\Mcp\Actions;

use Capell\Mcp\Data\AuthenticatedMcpClientData;
use Capell\Mcp\Data\CapabilityInvocationData;
use Capell\Mcp\Models\CapellMcpConfirmation;
use Capell\Mcp\Models\CapellMcpToken;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array<string, mixed> run(string $confirmationToken, array<string, mixed> $payload, ?AuthenticatedMcpClientData $client = null, ?CapellMcpToken $token = null, ?Authenticatable $user = null)
 */
final class ConfirmMcpCapabilityAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(
        string $confirmationToken,
        array $payload,
        ?AuthenticatedMcpClientData $client = null,
        ?CapellMcpToken $token = null,
        ?Authenticatable $user = null,
    ): array {
        $client ??= app()->bound(AuthenticatedMcpClientData::class) ? app(AuthenticatedMcpClientData::class) : null;
        $token ??= app()->bound(CapellMcpToken::class) ? app(CapellMcpToken::class) : null;
        $user ??= request()->user();

        $confirmation = CapellMcpConfirmation::query()
            ->where('token', $confirmationToken)
            ->first();

        if (! $confirmation instanceof CapellMcpConfirmation || ! $confirmation->isUsable()) {
            throw new AuthorizationException('The MCP confirmation token is invalid or expired.');
        }

        if ($token === null || (int) $confirmation->mcp_token_id !== (int) $token->getKey()) {
            throw new AuthorizationException('The MCP confirmation token does not belong to this client.');
        }

        if ($user === null || (string) $confirmation->user_type !== $user->getMorphClass() || (int) $confirmation->user_id !== (int) $user->getAuthIdentifier()) {
            throw new AuthorizationException('The MCP confirmation token does not belong to this user.');
        }

        if ($confirmation->payload_hash !== InvokeMcpCapabilityPreviewAction::payloadHash($payload)) {
            throw new AuthorizationException('The MCP confirmation payload has changed.');
        }

        $registry = resolve(CapellMcpCapabilityRegistry::class);
        $capability = $registry->get($confirmation->capability_key);

        if ($client !== null && ! $client->can($capability->scope)) {
            throw new AuthorizationException(sprintf('MCP scope [%s] is required.', $capability->scope));
        }

        if ($capability->policyAbility !== null) {
            Gate::forUser($user)->authorize($capability->policyAbility);
        }

        $action = resolve($capability->actionClass);
        $result = $action->execute(new CapabilityInvocationData($capability, $payload, $client, $user));

        $confirmation->forceFill(['used_at' => now()])->save();

        AuditMcpCapabilityAction::run(
            event: $capability->auditEvent ?? 'capell_mcp.capability.confirmed',
            capabilityKey: $capability->key,
            scope: $capability->scope,
            payload: $payload,
            result: $result,
            token: $token,
            user: $user,
        );

        return [
            'mode' => 'confirmed',
            'capability' => $capability->key,
            'result' => $result->toPayload(),
        ];
    }
}
