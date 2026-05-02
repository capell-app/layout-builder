<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Models\SiteSearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSearchResultClickAction
{
    use AsAction;

    public function handle(SiteSearchLog $log, string $url): SiteSearchLog
    {
        $log->forceFill([
            'clicked_result_url' => $url,
        ]);

        $log->save();

        return $log;
    }
}
