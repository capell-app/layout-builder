<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Models\SearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSearchResultClickAction
{
    use AsAction;

    public function handle(SearchLog $log, string $url): SearchLog
    {
        $log->forceFill([
            'clicked_result_url' => $url,
        ]);

        $log->save();

        return $log;
    }
}
