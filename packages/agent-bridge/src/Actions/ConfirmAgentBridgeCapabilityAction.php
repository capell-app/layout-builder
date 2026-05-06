<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Actions;

use Capell\AgentBridge\Data\AuthenticatedAgentBridgeClientData;
use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Models\CapellAgentBridgeConfirmation;
use Capell\AgentBridge\Models\CapellAgentBridgeToken;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array<string, mixed> run(string $confirmationToken, array<string, mixed> $payload, ?AuthenticatedAgentBridgeClientData $client = null, ?CapellAgentBridgeToken $token = null, ?Authenticatable $user = null)
 */
final class ConfirmAgentBridgeCapabilityAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(
        string $confirmationToken,
        array $payload,
        ?AuthenticatedAgentBridgeClientData $client = null,
        ?CapellAgentBridgeToken $token = null,
        ?Authenticatable $user = null,
    ): array {
        $client ??= app()->bound(AuthenticatedAgentBridgeClientData::class) ? resolve(AuthenticatedAgentBridgeClientData::class) : null;
        $token ??= app()->bound(CapellAgentBridgeToken::class) ? resolve(CapellAgentBridgeToken::class) : null;
        $user ??= request()->user();

        $confirmation = CapellAgentBridgeConfirmation::query()
            ->where('token', $confirmationToken)
            ->first();

        throw_if(! $confirmation instanceof CapellAgentBridgeConfirmation || ! $confirmation->isUsable(), AuthorizationException::class, 'The Agent Bridge confirmation token is invalid or expired.');

        throw_if($token === null || $confirmation->agent_bridge_token_id !== $token->getKey(), AuthorizationException::class, 'The Agent Bridge confirmation token does not belong to this client.');

        throw_if($user === null || (string) $confirmation->user_type !== $user->getMorphClass() || (int) $confirmation->user_id !== (int) $user->getAuthIdentifier(), AuthorizationException::class, 'The Agent Bridge confirmation token does not belong to this user.');

        throw_if($confirmation->payload_hash !== InvokeAgentBridgeCapabilityPreviewAction::payloadHash($payload), AuthorizationException::class, 'The Agent Bridge confirmation payload has changed.');

        $registry = resolve(CapellAgentBridgeCapabilityRegistry::class);
        $capability = $registry->get($confirmation->capability_key);

        if ($client !== null && ! $client->can($capability->scope)) {
            throw new AuthorizationException(sprintf('Agent Bridge scope [%s] is required.', $capability->scope));
        }

        if ($capability->policyAbility !== null) {
            Gate::forUser($user)->authorize($capability->policyAbility);
        }

        $action = resolve($capability->actionClass);
        $result = $action->execute(new CapabilityInvocationData($capability, $payload, $client, $user));

        $confirmation->forceFill(['used_at' => now()])->save();

        AuditAgentBridgeCapabilityAction::run(
            event: $capability->auditEvent ?? 'capell_agent-bridge.capability.confirmed',
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
