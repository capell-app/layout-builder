<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Models\AnalyticsEvent;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordAnalyticsEventAction
{
    use AsAction;

    public function handle(?string $visitUuid, AnalyticsEventData $data, ?string $occurredAt = null): ?AnalyticsEvent
    {
        /** @var AnalyticsEvent|null $event */
        $event = RecordAnalyticsEventsAction::run($visitUuid, [[
            'data' => $data,
            'occurred_at' => $occurredAt,
        ]])->first();

        return $event;
    }
}
