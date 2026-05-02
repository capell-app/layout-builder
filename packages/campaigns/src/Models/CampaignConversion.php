<?php

declare(strict_types=1);

namespace Capell\Campaigns\Models;

use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Campaigns\Data\ConversionAttributionData;
use Capell\Campaigns\Database\Factories\CampaignConversionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CampaignConversion extends Model
{
    /** @use HasFactory<CampaignConversionFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'campaign_group_id',
        'campaign_landing_page_id',
        'campaign_conversion_goal_id',
        'analytics_visit_id',
        'analytics_event_id',
        'source_type',
        'source_id',
        'site_id',
        'language_id',
        'attribution',
        'converted_at',
    ];

    protected static string $factory = CampaignConversionFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-campaigns.tables.conversions');

        return is_string($tableName) ? $tableName : 'campaign_conversions';
    }

    public function campaignGroup(): BelongsTo
    {
        return $this->belongsTo(CampaignGroup::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(CampaignLandingPage::class, 'campaign_landing_page_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(CampaignConversionGoal::class, 'campaign_conversion_goal_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisit::class, 'analytics_visit_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AnalyticsEvent::class, 'analytics_event_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attribution' => ConversionAttributionData::class,
            'converted_at' => 'immutable_datetime',
        ];
    }
}
