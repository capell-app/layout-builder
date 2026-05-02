<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordCustomActionAction
{
    use AsAction;

    public function handle(?string $visitUuid, AnalyticsEventData $data, ?string $occurredAt = null): ?AnalyticsEvent
    {
        if ($data->type !== AnalyticsEventType::Custom || $data->eventName === null || trim($data->eventName) === '') {
            return null;
        }

        return RecordAnalyticsEventAction::run($visitUuid, $data, $occurredAt);
    }
}
