<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchConsoleUrlMetric extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    protected function scopeDecliningPages(Builder $query, int $siteId, int $limit = 10): Builder
    {
        $latestMetric = self::query()
            ->where('site_id', $siteId)
            ->orderByDesc('window_end')
            ->orderByDesc('window_start')
            ->first(['window_start', 'window_end']);

        if (! $latestMetric instanceof self) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('site_id', $siteId)
            ->whereDate('window_start', $latestMetric->window_start->toDateString())
            ->whereDate('window_end', $latestMetric->window_end->toDateString())
            ->where('click_delta', '<', 0)
            ->orderBy('click_delta')
            ->limit($limit);
    }

    protected function casts(): array
    {
        return [
            'window_start' => 'date',
            'window_end' => 'date',
            'ctr' => 'float',
            'average_position' => 'float',
            'previous_ctr' => 'float',
            'previous_average_position' => 'float',
            'position_delta' => 'float',
            'synced_at' => 'datetime',
        ];
    }
}
