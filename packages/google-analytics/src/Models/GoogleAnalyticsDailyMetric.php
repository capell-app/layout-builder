<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Models;

use Illuminate\Database\Eloquent\Model;

final class GoogleAnalyticsDailyMetric extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        $tableName = config('capell-google-analytics.tables.daily_metrics');

        return is_string($tableName) ? $tableName : 'google_analytics_daily_metrics';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metric_date' => 'immutable_date',
            'engagement_rate' => 'float',
            'average_session_duration' => 'float',
        ];
    }
}
