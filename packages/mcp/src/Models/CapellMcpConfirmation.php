<?php

declare(strict_types=1);

namespace Capell\Mcp\Models;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $token
 * @property int $mcp_token_id
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
 * @property CapellMcpToken $mcpToken
 */
final class CapellMcpConfirmation extends Model
{
    protected $fillable = [
        'token',
        'mcp_token_id',
        'capability_key',
        'scope',
        'payload_hash',
        'payload',
        'preview',
        'expires_at',
        'used_at',
    ];

    protected $table = 'capell_mcp_confirmations';

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /** @return BelongsTo<CapellMcpToken, $this> */
    public function mcpToken(): BelongsTo
    {
        return $this->belongsTo(CapellMcpToken::class, 'mcp_token_id');
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
