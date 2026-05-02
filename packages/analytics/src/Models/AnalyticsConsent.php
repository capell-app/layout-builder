<?php

declare(strict_types=1);

namespace Capell\Analytics\Models;

use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Database\Factories\AnalyticsConsentFactory;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsConsent extends Model
{
    /** @use HasFactory<AnalyticsConsentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static string $factory = AnalyticsConsentFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-analytics.tables.consents');

        return is_string($tableName) ? $tableName : 'analytics_consents';
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisit::class, 'visit_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent_region' => AnalyticsConsentRegion::class,
            'status' => AnalyticsConsentStatus::class,
            'categories' => AnalyticsConsentData::class,
            'terms_accepted_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
        ];
    }
}
