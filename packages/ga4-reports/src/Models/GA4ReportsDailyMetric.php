<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Models;

use Illuminate\Database\Eloquent\Model;

final class GA4ReportsDailyMetric extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        $tableName = config('capell-ga4-reports.tables.daily_metrics');

        return is_string($tableName) ? $tableName : 'ga4_reports_daily_metrics';
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
