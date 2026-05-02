<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordClickAction
{
    use AsAction;

    public function handle(?string $visitUuid, AnalyticsEventData $data, ?string $occurredAt = null): ?AnalyticsEvent
    {
        if ($data->type !== AnalyticsEventType::Click || trim($data->url) === '') {
            return null;
        }

        if ($this->missingClickTarget($data)) {
            return null;
        }

        return RecordAnalyticsEventAction::run($visitUuid, $data, $occurredAt);
    }

    private function missingClickTarget(AnalyticsEventData $data): bool
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
