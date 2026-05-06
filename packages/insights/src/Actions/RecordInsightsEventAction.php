<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Models\InsightsEvent;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordInsightsEventAction
{
    use AsAction;

    public function handle(?string $visitUuid, InsightsEventData $data, ?string $occurredAt = null): ?InsightsEvent
    {
        /** @var InsightsEvent|null $event */
        $event = RecordInsightsEventsAction::run($visitUuid, [[
            'data' => $data,
            'occurred_at' => $occurredAt,
        ]])->first();

        return $event;
    }
}
