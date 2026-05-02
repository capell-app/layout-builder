<?php

declare(strict_types=1);

namespace Capell\Mcp\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $mcp_token_id
 * @property string $event
 * @property string|null $capability_key
 * @property string|null $scope
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $result
 * @property Authenticatable $user
 */
final class CapellMcpAuditEntry extends Model
{
    protected $fillable = [
        'mcp_token_id',
        'event',
        'capability_key',
        'scope',
        'payload',
        'result',
        'ip_address',
        'user_agent',
    ];

    protected $table = 'capell_mcp_audit_entries';

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
            'result' => 'array',
        ];
    }
}
