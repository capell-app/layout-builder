<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Core\Models\Site;
use Capell\Newsletter\Enums\ConsentEventType;
use Capell\Newsletter\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentEvent extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'subscriber_id',
        'site_id',
        'event_type',
        'subscriber_status',
        'source_type',
        'source_id',
        'provider_connection_id',
        'evidence',
        'metadata',
        'occurred_at',
    ];

    protected $table = 'newsletter_consent_events';

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => ConsentEventType::class,
            'subscriber_status' => SubscriberStatus::class,
            'evidence' => 'encrypted:array',
            'metadata' => 'encrypted:array',
            'occurred_at' => 'datetime',
        ];
    }
}
