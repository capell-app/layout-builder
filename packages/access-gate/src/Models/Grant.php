<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Capell\AccessGate\Database\Factories\GrantFactory;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grant extends AccessGateModel
{
    /** @use HasFactory<GrantFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'access_area_id',
        'registration_id',
        'subject_type',
        'subject_id',
        'user_id',
        'email',
        'status',
        'starts_at',
        'expires_at',
        'revoked_at',
        'discount_label',
        'discount_code',
        'discount_expires_at',
        'discount_metadata',
        'metadata',
    ];

    protected $table = 'access_gate_grants';

    protected static string $factory = GrantFactory::class;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'access_area_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function claimTokens(): HasMany
    {
        return $this->hasMany(ClaimToken::class, 'grant_id');
    }

    public function browserTokens(): HasMany
    {
        return $this->hasMany(BrowserToken::class, 'grant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'grant_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_type' => GrantSubjectType::class,
            'status' => GrantStatus::class,
            'metadata' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'discount_expires_at' => 'datetime',
            'discount_metadata' => 'array',
        ];
    }
}
