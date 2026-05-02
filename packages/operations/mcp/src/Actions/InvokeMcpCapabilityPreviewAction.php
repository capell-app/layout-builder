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
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array<string, mixed> run(string $capabilityKey, array<string, mixed> $payload, ?AuthenticatedMcpClientData $client = null, ?CapellMcpToken $token = null, ?Authenticatable $user = null)
 */
final class InvokeMcpCapabilityPreviewAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function payloadHash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(
        string $capabilityKey,
        array $payload,
        ?AuthenticatedMcpClientData $client = null,
        ?CapellMcpToken $token = null,
        ?Authenticatable $user = null,
    ): array {
        $registry = resolve(CapellMcpCapabilityRegistry::class);
        $capability = $registry->get($capabilityKey);

        $client ??= app()->bound(AuthenticatedMcpClientData::class) ? app(AuthenticatedMcpClientData::class) : null;
        $token ??= app()->bound(CapellMcpToken::class) ? app(CapellMcpToken::class) : null;
        $user ??= request()->user();

        if ($client !== null && ! $client->can($capability->scope)) {
            throw new AuthorizationException(sprintf('MCP scope [%s] is required.', $capability->scope));
        }

        if ($capability->policyAbility !== null && $user !== null) {
            Gate::forUser($user)->authorize($capability->policyAbility);
        }

        $action = resolve($capability->actionClass);
        $preview = $action->preview(new CapabilityInvocationData($capability, $payload, $client, $user));

        if (! $capability->needsConfirmation()) {
            $result = $action->execute(new CapabilityInvocationData($capability, $payload, $client, $user));

            AuditMcpCapabilityAction::run(
                event: $capability->auditEvent ?? 'capell_mcp.capability.executed',
                capabilityKey: $capability->key,
                scope: $capability->scope,
                payload: $payload,
                result: $result,
                token: $token,
                user: $user,
            );

            return [
                'mode' => 'executed',
                'capability' => $capability->key,
                'result' => $result->toPayload(),
            ];
        }

        $confirmation = new CapellMcpConfirmation([
            'token' => Str::random(64),
            'mcp_token_id' => $token?->getKey(),
            'capability_key' => $capability->key,
            'scope' => $capability->scope,
            'payload_hash' => self::payloadHash($payload),
            'payload' => $payload,
            'preview' => $preview->toPayload(),
            'expires_at' => now()->addMinutes((int) config('capell-mcp.confirmation_ttl_minutes', 10)),
        ]);

        if ($user !== null) {
            $confirmation->user()->associate($user);
        }

        $confirmation->save();

        AuditMcpCapabilityAction::run(
            event: 'capell_mcp.capability.previewed',
            capabilityKey: $capability->key,
            scope: $capability->scope,
            payload: $payload,
            result: $preview,
            token: $token,
            user: $user,
        );

        return [
            'mode' => 'preview',
            'capability' => $capability->key,
            'confirmationToken' => $confirmation->token,
            'expiresAt' => $confirmation->expires_at->toIso8601String(),
            'preview' => $preview->toPayload(),
        ];
    }
}
