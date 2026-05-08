<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Newsletter\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $attempts
 * @property-read Subscriber|null $subscriber
 * @property-read ProviderAudience|null $providerAudience
 * @property-read ProviderConnection|null $providerConnection
 */
class SyncAttempt extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'subscriber_id',
        'provider_connection_id',
        'provider_audience_id',
        'operation',
        'sync_status',
        'payload_hash',
        'attempts',
        'error_message',
        'last_attempted_at',
        'next_retry_at',
    ];

    protected $table = 'newsletter_sync_attempts';

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    public function providerAudience(): BelongsTo
    {
        return $this->belongsTo(ProviderAudience::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sync_status' => SyncStatus::class,
            'last_attempted_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }
}
