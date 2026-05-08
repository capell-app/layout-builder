<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Capell\AccessGate\Database\Factories\AreaFactory;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Enums\RegistrationPolicy;
use Capell\AccessGate\Enums\TokenPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends AccessGateModel
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'key',
        'name',
        'status',
        'identity_mode',
        'approval_strategy',
        'approval_limit',
        'grant_duration_days',
        'registration_policy',
        'token_policy',
        'public_allowlist',
        'claim_url_hosts',
        'gate_view',
        'metadata',
        'discount_label',
        'discount_code',
        'discount_expires_at',
        'discount_metadata',
    ];

    protected $table = 'access_gate_areas';

    protected static string $factory = AreaFactory::class;

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'access_area_id');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(Grant::class, 'access_area_id');
    }

    public function claimTokens(): HasMany
    {
        return $this->hasMany(ClaimToken::class, 'access_area_id');
    }

    public function browserTokens(): HasMany
    {
        return $this->hasMany(BrowserToken::class, 'access_area_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'access_area_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AccessAreaStatus::class,
            'identity_mode' => IdentityMode::class,
            'approval_strategy' => ApprovalStrategy::class,
            'registration_policy' => RegistrationPolicy::class,
            'token_policy' => TokenPolicy::class,
            'public_allowlist' => 'array',
            'claim_url_hosts' => 'array',
            'metadata' => 'array',
            'discount_expires_at' => 'datetime',
            'discount_metadata' => 'array',
        ];
    }
}
