<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDiscoverySiteProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'llms_txt_enabled' => 'bool',
            'llms_full_txt_enabled' => 'bool',
            'markdown_pages_enabled' => 'bool',
            'accept_markdown_enabled' => 'bool',
            'default_include_pages' => 'bool',
            'max_full_txt_pages' => 'int',
            'max_full_txt_bytes' => 'int',
            'cache_ttl_seconds' => 'int',
            'status' => AiDiscoveryStatusEnum::class,
        ];
    }
}
