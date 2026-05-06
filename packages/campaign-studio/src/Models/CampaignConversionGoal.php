<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Models;

use Capell\CampaignStudio\Database\Factories\CampaignConversionGoalFactory;
use Capell\CampaignStudio\Enums\ConversionGoalType;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignConversionGoal extends Model
{
    /** @use HasFactory<CampaignConversionGoalFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'campaign_group_id',
        'site_id',
        'name',
        'key',
        'type',
        'target',
        'value_amount',
        'is_primary',
        'is_active',
    ];

    protected static string $factory = CampaignConversionGoalFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-campaign-studio.tables.conversion_goals');

        return is_string($tableName) ? $tableName : 'campaign_conversion_goals';
    }

    public function campaignGroup(): BelongsTo
    {
        return $this->belongsTo(CampaignGroup::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(CampaignConversion::class, 'campaign_conversion_goal_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConversionGoalType::class,
            'value_amount' => 'decimal:2',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
