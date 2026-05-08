<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Capell\AccessGate\Database\Factories\BrowserTokenFactory;
use Capell\AccessGate\Enums\BrowserTokenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrowserToken extends AccessGateModel
{
    /** @use HasFactory<BrowserTokenFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'access_area_id',
        'grant_id',
        'token_hash',
        'status',
        'ip_hash',
        'user_agent',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'metadata',
    ];

    protected $table = 'access_gate_browser_tokens';

    protected static string $factory = BrowserTokenFactory::class;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'access_area_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class, 'grant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'browser_token_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BrowserTokenStatus::class,
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
