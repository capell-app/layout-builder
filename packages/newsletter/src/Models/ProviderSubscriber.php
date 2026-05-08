<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSubscriber extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'subscriber_id',
        'provider_audience_id',
        'remote_id',
        'remote_status',
        'synced_at',
    ];

    protected $table = 'newsletter_provider_subscribers';

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
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
            'synced_at' => 'datetime',
        ];
    }
}
