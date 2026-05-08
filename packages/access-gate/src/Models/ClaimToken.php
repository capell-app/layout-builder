<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Capell\AccessGate\Database\Factories\ClaimTokenFactory;
use Capell\AccessGate\Enums\ClaimTokenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClaimToken extends AccessGateModel
{
    /** @use HasFactory<ClaimTokenFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'access_area_id',
        'registration_id',
        'grant_id',
        'token_hash',
        'status',
        'expires_at',
        'consumed_at',
        'metadata',
    ];

    protected $table = 'access_gate_claim_tokens';

    protected static string $factory = ClaimTokenFactory::class;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'access_area_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class, 'grant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'claim_token_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClaimTokenStatus::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
