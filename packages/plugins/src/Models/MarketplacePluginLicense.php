<?php

declare(strict_types=1);

namespace Capell\Plugins\Models;

use Capell\Plugins\Enums\LicenseStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $activated_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $last_heartbeat_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class MarketplacePluginLicense extends Model
{
    use HasFactory;

    protected $table = 'marketplace_plugin_licenses';

    protected $hidden = ['encrypted_license_key'];

    protected $casts = [
        'encrypted_license_key' => 'encrypted',
        'status' => LicenseStatus::class,
        'metadata' => AsArrayObject::class,
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'seats' => 'integer',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlugin::class, 'marketplace_plugin_id');
    }

    public function isWithinGracePeriod(?DateTimeInterface $now = null): bool
    {
        if ($this->last_heartbeat_at === null) {
            return false;
        }

        $nowCarbon = $now === null ? now() : CarbonImmutable::parse($now->format(DATE_ATOM));
        $graceDays = config('capell-plugins.license_heartbeat.offline_grace_days', 14);

        return $this->last_heartbeat_at->greaterThanOrEqualTo($nowCarbon->subDays($graceDays));
    }
}
