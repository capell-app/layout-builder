<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Models;

use Illuminate\Database\Eloquent\Model;

final class GA4ReportsPageMetric extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        $tableName = config('capell-ga4-reports.tables.page_metrics');

        return is_string($tableName) ? $tableName : 'ga4_reports_page_metrics';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metric_date' => 'immutable_date',
        ];
    }
}
