<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Models;

use Capell\CampaignStudio\Database\Factories\CampaignLandingPageFactory;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignLandingPage extends Model
{
    /** @use HasFactory<CampaignLandingPageFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'campaign_group_id',
        'page_id',
        'headline',
        'primary_goal_id',
        'utm_content',
        'utm_term',
        'is_primary',
    ];

    protected static string $factory = CampaignLandingPageFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-campaign-studio.tables.landing_pages');

        return is_string($tableName) ? $tableName : 'campaign_landing_pages';
    }

    public function campaignGroup(): BelongsTo
    {
        return $this->belongsTo(CampaignGroup::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function primaryGoal(): BelongsTo
    {
        return $this->belongsTo(CampaignConversionGoal::class, 'primary_goal_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(CampaignConversion::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
