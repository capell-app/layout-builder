<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Actions;

use Capell\AgentBridge\Data\CapabilityResultData;
use Capell\AgentBridge\Models\CapellAgentBridgeAuditEntry;
use Capell\AgentBridge\Models\CapellAgentBridgeToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static CapellAgentBridgeAuditEntry run(string $event, ?string $capabilityKey = null, ?string $scope = null, array<string, mixed> $payload = [], ?CapabilityResultData $result = null, ?CapellAgentBridgeToken $token = null, ?Authenticatable $user = null)
 */
final class AuditAgentBridgeCapabilityAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        string $event,
        ?string $capabilityKey = null,
        ?string $scope = null,
        array $payload = [],
        ?CapabilityResultData $result = null,
        ?CapellAgentBridgeToken $token = null,
        ?Authenticatable $user = null,
    ): CapellAgentBridgeAuditEntry {
        $request = request();

        $entry = new CapellAgentBridgeAuditEntry([
            'agent_bridge_token_id' => $token?->getKey(),
            'event' => $event,
            'capability_key' => $capabilityKey,
            'scope' => $scope,
            'payload' => $payload === [] ? null : $payload,
            'result' => $result?->toPayload(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($user instanceof Model) {
            $entry->user()->associate($user);
        }

        $entry->save();

        return $entry;
    }
}
