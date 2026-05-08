<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderAudience extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'provider_connection_id',
        'name',
        'remote_id',
        'settings',
        'is_default',
        'sync_subscribed_only',
    ];

    protected $table = 'newsletter_provider_audiences';

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    public function interestMappings(): HasMany
    {
        return $this->hasMany(ProviderInterestMapping::class);
    }

    public function providerSubscribers(): HasMany
    {
        return $this->hasMany(ProviderSubscriber::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_default' => 'boolean',
            'sync_subscribed_only' => 'boolean',
        ];
    }
}
