<?php

declare(strict_types=1);

namespace Capell\Analytics\Models;

use Capell\Analytics\Database\Factories\AnalyticsVisitFactory;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsVisit extends Model
{
    /** @use HasFactory<AnalyticsVisitFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static string $factory = AnalyticsVisitFactory::class;

    public function getTable(): string
    {
        return (string) config('capell-analytics.tables.visits', 'analytics_visits');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(AnalyticsConsent::class, 'visit_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'visit_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent_region' => AnalyticsConsentRegion::class,
            'consent_status' => AnalyticsConsentStatus::class,
            'started_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ];
    }
}
