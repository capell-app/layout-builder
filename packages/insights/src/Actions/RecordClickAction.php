<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordClickAction
{
    use AsAction;

    public function handle(?string $visitUuid, InsightsEventData $data, ?string $occurredAt = null): ?InsightsEvent
    {
        if ($data->type !== InsightsEventType::Click || trim($data->url) === '') {
            return null;
        }

        if ($this->missingClickTarget($data)) {
            return null;
        }

        return RecordInsightsEventAction::run($visitUuid, $data, $occurredAt);
    }

    private function missingClickTarget(InsightsEventData $data): bool
    {
        return $this->blank($data->targetSelector)
            && $this->blank($data->label)
            && $this->blank($data->location);
    }

    private function blank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
