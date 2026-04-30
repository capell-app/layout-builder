<?php

declare(strict_types=1);

namespace Capell\Analytics\Models;

use Capell\Analytics\Database\Factories\AnalyticsEventFactory;
use Capell\Analytics\Enums\AnalyticsEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    /** @use HasFactory<AnalyticsEventFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static string $factory = AnalyticsEventFactory::class;

    public function getTable(): string
    {
        return (string) config('capell-analytics.tables.events', 'analytics_events');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisit::class, 'visit_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnalyticsEventType::class,
            'occurred_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }
}
