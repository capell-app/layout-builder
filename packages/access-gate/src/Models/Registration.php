<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Capell\AccessGate\Database\Factories\RegistrationFactory;
use Capell\AccessGate\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends AccessGateModel
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'access_area_id',
        'email',
        'email_normalized',
        'single_registration_key',
        'user_id',
        'status',
        'requested_url',
        'requested_host',
        'position',
        'field_values',
        'metadata',
        'requested_at',
        'approved_at',
        'rejected_at',
        'claimed_at',
        'expired_at',
    ];

    protected $table = 'access_gate_registrations';

    protected static string $factory = RegistrationFactory::class;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'access_area_id');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(Grant::class, 'registration_id');
    }

    public function claimTokens(): HasMany
    {
        return $this->hasMany(ClaimToken::class, 'registration_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'registration_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'field_values' => 'array',
            'metadata' => 'array',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'claimed_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }
}
