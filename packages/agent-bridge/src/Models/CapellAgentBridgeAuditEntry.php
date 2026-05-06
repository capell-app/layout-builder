<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $agent_bridge_token_id
 * @property string $event
 * @property string|null $capability_key
 * @property string|null $scope
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $result
 * @property Authenticatable $user
 */
final class CapellAgentBridgeAuditEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_bridge_token_id',
        'event',
        'capability_key',
        'scope',
        'payload',
        'result',
        'ip_address',
        'user_agent',
    ];

    protected $table = 'capell_agent-bridge_audit_entries';

    /** @return BelongsTo<CapellAgentBridgeToken, $this> */
    public function agentBridgeToken(): BelongsTo
    {
        return $this->belongsTo(CapellAgentBridgeToken::class, 'agent_bridge_token_id');
    }

    /** @return MorphTo<Model, $this> */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
        ];
    }
}
