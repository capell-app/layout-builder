<?php

declare(strict_types=1);

namespace Capell\Mcp\Actions;

use Capell\Mcp\Data\CapabilityResultData;
use Capell\Mcp\Models\CapellMcpAuditEntry;
use Capell\Mcp\Models\CapellMcpToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static CapellMcpAuditEntry run(string $event, ?string $capabilityKey = null, ?string $scope = null, array<string, mixed> $payload = [], ?CapabilityResultData $result = null, ?CapellMcpToken $token = null, ?Authenticatable $user = null)
 */
final class AuditMcpCapabilityAction
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
        ?CapellMcpToken $token = null,
        ?Authenticatable $user = null,
    ): CapellMcpAuditEntry {
        $request = request();

        $entry = new CapellMcpAuditEntry([
            'mcp_token_id' => $token?->getKey(),
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
