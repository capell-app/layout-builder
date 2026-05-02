<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Data\ConversionAttributionData;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildConversionAttributionAction
{
    use AsAction;

    public function handle(?Model $visit, ?Model $event): ConversionAttributionData
    {
        return new ConversionAttributionData(
            landingUrl: $this->stringAttribute($visit, 'landing_url'),
            referrerUrl: $this->stringAttribute($visit, 'referrer_url'),
            utmSource: $this->stringAttribute($visit, 'utm_source'),
            utmMedium: $this->stringAttribute($visit, 'utm_medium'),
            utmCampaign: $this->stringAttribute($visit, 'utm_campaign'),
            utmTerm: $this->metadataValue($event, 'utm_term'),
            utmContent: $this->metadataValue($event, 'utm_content'),
            eventName: $this->stringAttribute($event, 'event_name'),
            eventLabel: $this->stringAttribute($event, 'label'),
            eventLocation: $this->stringAttribute($event, 'location'),
            firstTouchCampaign: $this->stringAttribute($visit, 'utm_campaign'),
            lastTouchCampaign: $this->metadataValue($event, 'utm_campaign') ?? $this->stringAttribute($visit, 'utm_campaign'),
        );
    }

    private function stringAttribute(?Model $model, string $attribute): ?string
    {
        $value = $model?->getAttribute($attribute);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function metadataValue(?Model $event, string $key): ?string
    {
        $metadata = $event?->getAttribute('metadata');

        if (is_object($metadata)) {
            $metadata = get_object_vars($metadata);
        }

        if (is_array($metadata) && isset($metadata[$key]) && is_string($metadata[$key])) {
            return $metadata[$key];
        }

        return null;
    }
}
