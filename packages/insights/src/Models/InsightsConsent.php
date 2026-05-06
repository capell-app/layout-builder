<?php

declare(strict_types=1);

namespace Capell\Insights\Models;

use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Database\Factories\InsightsConsentFactory;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsightsConsent extends Model
{
    /** @use HasFactory<InsightsConsentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static string $factory = InsightsConsentFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-insights.tables.consents');

        return is_string($tableName) ? $tableName : 'insights_consents';
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(InsightsVisit::class, 'visit_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent_region' => InsightsConsentRegion::class,
            'status' => InsightsConsentStatus::class,
            'categories' => InsightsConsentData::class,
            'terms_accepted_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
        ];
    }
}
