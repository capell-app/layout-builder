<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Models;

use Capell\CampaignStudio\Data\CampaignCtaActionData;
use Capell\CampaignStudio\Data\UtmData;
use Capell\CampaignStudio\Database\Factories\CampaignCtaBlockFactory;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\LaravelData\DataCollection;

class CampaignCtaBlock extends Model
{
    /** @use HasFactory<CampaignCtaBlockFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'campaign_group_id',
        'site_id',
        'name',
        'key',
        'headline',
        'body',
        'actions',
        'default_utm',
        'is_active',
    ];

    protected static string $factory = CampaignCtaBlockFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-campaign-studio.tables.cta_blocks');

        return is_string($tableName) ? $tableName : 'campaign_cta_blocks';
    }

    public function campaignGroup(): BelongsTo
    {
        return $this->belongsTo(CampaignGroup::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actions' => DataCollection::class . ':' . CampaignCtaActionData::class,
            'default_utm' => UtmData::class,
            'is_active' => 'boolean',
        ];
    }
}
