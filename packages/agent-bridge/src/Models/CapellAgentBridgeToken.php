<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Models;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $token_hash
 * @property array<int, string> $scopes
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $expires_at
 * @property Authenticatable|null $user
 */
final class CapellAgentBridgeToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token_hash',
        'scopes',
        'expires_at',
    ];

    protected $table = 'capell_agent-bridge_tokens';

    public static function hashPlainTextToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    public static function generatePlainTextToken(): string
    {
        return config('capell-agent-bridge.token_prefix', 'cagent-bridge_') . Str::random(48);
    }

    public function canUseScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true) || in_array('*', $this->scopes, true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof CarbonImmutable && $this->expires_at->isPast();
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
            'scopes' => 'array',
            'last_used_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
