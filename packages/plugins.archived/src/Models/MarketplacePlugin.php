<?php

declare(strict_types=1);

namespace Capell\Plugins\Models;

use Capell\Plugins\Database\Factories\MarketplacePluginFactory;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\Factory;
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

    protected static function newFactory(): Factory
    {
        return MarketplacePluginFactory::new();
    }

    /**
     * Scope: plugins considered "installed" — either they have at least one
     * license row (paid plugins activated via the admin UI) or their
     * `composer_name` maps to a real vendor directory on disk (free plugins).
     *
     * Replaces a previous hard-coded `mosaic,blog,address,assistant` list
     * that could drift from reality. The in-memory filtering step is bounded
     * by the number of marketplace plugins (tens at most) so loading all
     * `composer_name` values first is cheap.
     */
    protected function scopeInstalled(Builder $query): Builder
    {
        $freeInstalledIds = static::query()
            ->whereNotNull('composer_name')
            ->get(['id', 'composer_name'])
            ->filter(fn (MarketplacePlugin $plugin): bool => is_dir(base_path('vendor/' . $plugin->composer_name)))
            ->pluck('id')
            ->all();

        return $query->where(function (Builder $inner) use ($freeInstalledIds): void {
            $inner->whereHas('licenses');

            if ($freeInstalledIds !== []) {
                $inner->orWhereIn('id', $freeInstalledIds);
            }
        });
    }
}
