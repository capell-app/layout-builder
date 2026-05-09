<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerDirectiveEnum;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerPurposeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDiscoveryCrawlerRule extends Model
{
    use HasFactory;

    protected $guarded = [];

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
            'purpose' => AiDiscoveryCrawlerPurposeEnum::class,
            'directive' => AiDiscoveryCrawlerDirectiveEnum::class,
            'enabled' => 'bool',
            'crawl_delay_seconds' => 'int',
        ];
    }
}
