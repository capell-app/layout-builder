<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Models;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $token
 * @property int $agent_bridge_token_id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string $capability_key
 * @property string $scope
 * @property string $payload_hash
 * @property array<string, mixed> $payload
 * @property array<string, mixed> $preview
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $used_at
 * @property Authenticatable $user
 * @property CapellAgentBridgeToken $agentBridgeToken
 */
final class CapellAgentBridgeConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'agent_bridge_token_id',
        'capability_key',
        'scope',
        'payload_hash',
        'payload',
        'preview',
        'expires_at',
        'used_at',
    ];

    protected $table = 'capell_agent-bridge_confirmations';

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

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
            'preview' => 'array',
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
        ];
    }
}
