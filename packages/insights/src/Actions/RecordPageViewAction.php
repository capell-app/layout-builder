<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordPageViewAction
{
    use AsAction;

    public function handle(?string $visitUuid, InsightsEventData $data, ?string $occurredAt = null): ?InsightsEvent
    {
        if ($data->type !== InsightsEventType::PageView || trim($data->url) === '') {
            return null;
        }

        return RecordInsightsEventAction::run($visitUuid, $data, $occurredAt);
    }
}
