<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSeoSnapshot extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    protected function casts(): array
    {
        return [
            'issue_keys' => 'array',
            'passed_check_keys' => 'array',
            'computed_at' => 'datetime',
        ];
    }
}
