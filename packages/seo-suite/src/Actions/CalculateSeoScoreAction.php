<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\SeoSuite\Data\SeoIssueData;
use Lorisleiva\Actions\Concerns\AsAction;

final class CalculateSeoScoreAction
{
    use AsAction;

    /**
     * @param  list<SeoIssueData>  $issues
     */
    public function handle(array $issues): int
    {
        $penalty = array_sum(array_map(
            fn (SeoIssueData $issue): int => $issue->severity->penalty(),
            $issues,
        ));

        return max(0, 100 - $penalty);
    }
}
