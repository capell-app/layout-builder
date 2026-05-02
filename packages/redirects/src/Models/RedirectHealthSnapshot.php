<?php

declare(strict_types=1);

namespace Capell\Redirects\Models;

use Capell\Core\Models\PageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedirectHealthSnapshot extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function pageUrl(): BelongsTo
    {
        return $this->belongsTo(PageUrl::class);
    }

    protected function casts(): array
    {
        return [
            'has_chain' => 'boolean',
            'has_loop' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }
}
