<?php

declare(strict_types=1);

namespace Capell\Insights\Models;

use Capell\Insights\Database\Factories\InsightsVisitFactory;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsightsVisit extends Model
{
    /** @use HasFactory<InsightsVisitFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static string $factory = InsightsVisitFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-insights.tables.visits');

        return is_string($tableName) ? $tableName : 'insights_visits';
    }

    public function consents(): HasMany
    {
        return $this->hasMany(InsightsConsent::class, 'visit_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InsightsEvent::class, 'visit_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent_region' => InsightsConsentRegion::class,
            'consent_status' => InsightsConsentStatus::class,
            'started_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ];
    }
}
