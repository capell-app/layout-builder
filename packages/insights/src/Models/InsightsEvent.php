<?php

declare(strict_types=1);

namespace Capell\Insights\Models;

use Capell\Insights\Data\InsightsEventMetadataData;
use Capell\Insights\Database\Factories\InsightsEventFactory;
use Capell\Insights\Enums\InsightsEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsightsEvent extends Model
{
    /** @use HasFactory<InsightsEventFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static string $factory = InsightsEventFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-insights.tables.events');

        return is_string($tableName) ? $tableName : 'insights_events';
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(InsightsVisit::class, 'visit_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InsightsEventType::class,
            'occurred_at' => 'immutable_datetime',
            'metadata' => InsightsEventMetadataData::class,
        ];
    }
}
