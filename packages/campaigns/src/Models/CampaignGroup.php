<?php

declare(strict_types=1);

namespace Capell\Campaigns\Models;

use Capell\Campaigns\Database\Factories\CampaignGroupFactory;
use Capell\Campaigns\Enums\CampaignStatus;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignGroup extends Model
{
    /** @use HasFactory<CampaignGroupFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'status',
        'starts_at',
        'ends_at',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'budget_amount',
        'notes',
    ];

    protected static string $factory = CampaignGroupFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-campaigns.tables.groups');

        return is_string($tableName) ? $tableName : 'campaign_groups';
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(CampaignLandingPage::class);
    }

    public function ctaBlocks(): HasMany
    {
        return $this->hasMany(CampaignCtaBlock::class);
    }

    public function conversionGoals(): HasMany
    {
        return $this->hasMany(CampaignConversionGoal::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(CampaignConversion::class);
    }

    protected function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', CampaignStatus::Active)
            ->where(function (Builder $dateWindowQuery): void {
                $dateWindowQuery
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $dateWindowQuery): void {
                $dateWindowQuery
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'budget_amount' => 'decimal:2',
        ];
    }
}
