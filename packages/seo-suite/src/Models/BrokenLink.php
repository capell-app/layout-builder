<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokenLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'target_url',
        'http_status',
        'last_checked_at',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }
}
