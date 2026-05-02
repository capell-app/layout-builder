<?php

declare(strict_types=1);

namespace Capell\Campaigns\Listeners;

use Capell\Campaigns\Actions\BuildConversionAttributionAction;
use Capell\Campaigns\Actions\RecordFormSubmissionConversionAction;
use Capell\Campaigns\Actions\ResolveCampaignLandingPageFromUrlAction;
use Capell\Campaigns\Data\ConversionAttributionData;
use Capell\Campaigns\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class RecordFormSubmissionConversion
{
    public function handle(object $event): void
    {
        if (! $this->campaignTablesExist()) {
            return;
        }

        $form = $event->form ?? null;

        if (! is_object($form)) {
            return;
        }

        $handle = $form->handle ?? null;
        $formId = method_exists($form, 'getKey') ? (string) $form->getKey() : null;
        $targets = array_values(array_filter([
            is_string($handle) && $handle !== '' ? $handle : null,
            is_string($formId) && $formId !== '' ? $formId : null,
        ], static fn (?string $target): bool => $target !== null));

        if ($targets === []) {
            return;
        }

        $submission = $this->modelProperty($event, 'submission');
        $visit = $this->modelProperty($event, 'visit') ?? $this->modelProperty($event, 'analyticsVisit');
        $analyticsEvent = $this->modelProperty($event, 'event') ?? $this->modelProperty($event, 'analyticsEvent');
        $metadata = $this->metadata($event, $submission);
        $landingPage = ResolveCampaignLandingPageFromUrlAction::run($this->metadataValue($metadata, 'url'));
        $attribution = $this->attribution($visit, $analyticsEvent, $metadata, $landingPage);

        foreach ($targets as $target) {
            $conversion = RecordFormSubmissionConversionAction::run(
                $target,
                $visit,
                $analyticsEvent,
                $landingPage,
                $submission,
                $attribution,
            );

            if ($conversion !== null) {
                return;
            }
        }
    }

    private function modelProperty(object $event, string $property): ?Model
    {
        $eventProperties = get_object_vars($event);
        $value = $eventProperties[$property] ?? null;

        return $value instanceof Model ? $value : null;
    }

    private function campaignTablesExist(): bool
    {
        $goalsTableName = config('capell-campaigns.tables.conversion_goals', 'campaign_conversion_goals');
        $conversionsTableName = config('capell-campaigns.tables.conversions', 'campaign_conversions');
        $landingPagesTableName = config('capell-campaigns.tables.landing_pages', 'campaign_landing_pages');

        return is_string($goalsTableName)
            && is_string($conversionsTableName)
            && is_string($landingPagesTableName)
            && Schema::hasTable($goalsTableName)
            && Schema::hasTable($conversionsTableName)
            && Schema::hasTable($landingPagesTableName);
    }

    private function metadata(object $event, ?Model $submission): ?object
    {
        $eventProperties = get_object_vars($event);
        $metadata = $eventProperties['metadata'] ?? $eventProperties['meta'] ?? $submission?->getAttribute('meta');

        if (is_object($metadata)) {
            return $metadata;
        }

        return is_array($metadata) ? (object) $metadata : null;
    }

    private function attribution(
        ?Model $visit,
        ?Model $analyticsEvent,
        ?object $metadata,
        ?CampaignLandingPage $landingPage,
    ): ConversionAttributionData {
        $attribution = BuildConversionAttributionAction::run($visit, $analyticsEvent);
        $landingUrl = $this->metadataValue($metadata, 'url');
        $utmParameters = $this->utmParameters($landingUrl);

        return new ConversionAttributionData(
            landingUrl: $attribution->landingUrl ?? $landingUrl,
            referrerUrl: $attribution->referrerUrl ?? $this->metadataValue($metadata, 'referer'),
            utmSource: $attribution->utmSource ?? $utmParameters['utm_source'] ?? null,
            utmMedium: $attribution->utmMedium ?? $utmParameters['utm_medium'] ?? null,
            utmCampaign: $attribution->utmCampaign ?? $utmParameters['utm_campaign'] ?? $landingPage?->campaignGroup?->utm_campaign,
            utmTerm: $attribution->utmTerm ?? $utmParameters['utm_term'] ?? $landingPage?->utm_term,
            utmContent: $attribution->utmContent ?? $utmParameters['utm_content'] ?? $landingPage?->utm_content,
            eventName: $attribution->eventName ?? 'form_submission',
            eventLabel: $attribution->eventLabel,
            eventLocation: $attribution->eventLocation ?? 'lead-form',
            firstTouchCampaign: $attribution->firstTouchCampaign ?? $utmParameters['utm_campaign'] ?? null,
            lastTouchCampaign: $attribution->lastTouchCampaign ?? $utmParameters['utm_campaign'] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    private function utmParameters(?string $url): array
    {
        if (! is_string($url) || $url === '') {
            return [];
        }

        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return [];
        }

        $parameters = [];
        parse_str($query, $parameters);

        $utmParameters = [];

        foreach ($parameters as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (! str_starts_with($key, 'utm_')) {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            $utmParameters[$key] = $value;
        }

        return $utmParameters;
    }

    private function metadataValue(?object $metadata, string $key): ?string
    {
        if ($metadata === null) {
            return null;
        }

        $metadataValues = get_object_vars($metadata);
        $value = $metadataValues[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
