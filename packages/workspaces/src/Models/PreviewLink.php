<?php

declare(strict_types=1);

namespace Capell\Workspaces\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Persistent record of a signed preview URL that has been issued for a
 * workspace. The token is the authoritative validity key — signed URLs stay
 * tamper-resistant but revocation is enforced against this row, not the URL
 * signature TTL.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $token
 * @property string|null $issued_by_type
 * @property int|null $issued_by_id
 * @property CarbonImmutable $issued_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $last_accessed_at
 * @property int $access_count
 * @property-read Workspace $workspace
 */
class PreviewLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'token',
        'issued_by_type',
        'issued_by_id',
        'issued_at',
        'expires_at',
        'revoked_at',
        'last_accessed_at',
        'access_count',
    ];

    public static function generateToken(): string
    {
        return Str::lower(Str::random(48));
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function issuedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(?CarbonImmutable $now = null): bool
    {
        return $this->expires_at->lessThanOrEqualTo($now ?? CarbonImmutable::now());
    }

    public function isUsable(?CarbonImmutable $now = null): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired($now);
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_accessed_at' => 'immutable_datetime',
            'access_count' => 'integer',
        ];
    }
}
