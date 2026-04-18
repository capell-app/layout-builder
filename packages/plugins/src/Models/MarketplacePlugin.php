<?php

declare(strict_types=1);

namespace Capell\Plugins\Models;

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplacePlugin extends Model
{
    use HasFactory;

    protected $table = 'marketplace_plugins';

    protected $guarded = [];

    protected $casts = [
        'kind' => PluginKind::class,
        'license_model' => LicenseModel::class,
        'categories' => AsArrayObject::class,
        'screenshots' => AsArrayObject::class,
        'compatibility' => AsArrayObject::class,
        'capabilities' => AsArrayObject::class,
        'is_visible' => 'boolean',
        'price_monthly' => 'integer',
        'price_yearly' => 'integer',
        'price_once' => 'integer',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function licenses(): HasMany
    {
        return $this->hasMany(MarketplacePluginLicense::class);
    }

    public function auditLog(): HasMany
    {
        return $this->hasMany(PluginAuditLogEntry::class);
    }

    public function activeLicense(): ?MarketplacePluginLicense
    {
        return $this->licenses()
            ->whereIn('status', ['active', 'trial', 'past_due'])
            ->latest()
            ->first();
    }

    public function isInstalled(): bool
    {
        return is_dir(base_path('vendor/' . $this->composer_name));
    }
}
