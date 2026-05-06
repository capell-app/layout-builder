<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Models;

use Illuminate\Database\Eloquent\Model;

final class GA4ReportsSyncRun extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        $tableName = config('capell-ga4-reports.tables.sync_runs');

        return is_string($tableName) ? $tableName : 'ga4_reports_sync_runs';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'window_start' => 'immutable_date',
            'window_end' => 'immutable_date',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}
