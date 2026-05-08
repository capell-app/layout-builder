<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Capell\AccessGate\Database\Factories\EventFactory;
use Capell\AccessGate\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Event extends AccessGateModel
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'access_area_id',
        'registration_id',
        'grant_id',
        'claim_token_id',
        'browser_token_id',
        'user_id',
        'type',
        'subject_type',
        'subject_id',
        'payload',
        'metadata',
        'occurred_at',
    ];

    protected $table = 'access_gate_events';

    protected static string $factory = EventFactory::class;

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

    public function claimToken(): BelongsTo
    {
        return $this->belongsTo(ClaimToken::class, 'claim_token_id');
    }

    public function browserToken(): BelongsTo
    {
        return $this->belongsTo(BrowserToken::class, 'browser_token_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'payload' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
