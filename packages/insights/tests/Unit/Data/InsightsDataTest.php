<?php

declare(strict_types=1);

use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Data\InsightsEventMetadataData;
use Capell\Insights\Data\InsightsJourneyStepData;
use Capell\Insights\Enums\InsightsConsentCategory;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Carbon\CarbonImmutable;

it('serializes consent categories as data', function (): void {
    $data = InsightsConsentData::from([
        'essential' => true,
        'insights' => true,
        'marketing' => false,
        'preferences' => false,
    ]);

    expect($data->enabledCategories())->toBe([
        InsightsConsentCategory::Essential,
        InsightsConsentCategory::Insights,
    ]);
});

it('always treats essential consent as enabled', function (): void {
    $data = InsightsConsentData::from([
        'essential' => false,
        'insights' => false,
        'marketing' => false,
        'preferences' => false,
    ]);

    expect($data->enabledCategories())->toBe([
        InsightsConsentCategory::Essential,
    ]);
});

it('normalizes event data', function (): void {
    $data = InsightsEventData::from([
        'type' => 'click',
        'url' => 'https://example.test/path?token=secret',
        'title' => 'Example',
        'event_name' => 'cta_click',
        'label' => 'Book a demo',
        'location' => 'home.hero',
        'target_selector' => 'button[data-capell-insights]',
        'viewport_x' => 10,
        'viewport_y' => 20,
        'document_x' => 10,
        'document_y' => 520,
        'metadata' => ['nearest_landmark' => 'main'],
    ]);

    expect($data->type)->toBe(InsightsEventType::Click)
        ->and($data->path())->toBe('/path')
        ->and($data->metadata)->toBeInstanceOf(InsightsEventMetadataData::class)
        ->and($data->metadata?->nearestLandmark)->toBe('main');
});

it('casts event model metadata as data', function (): void {
    $event = new InsightsEvent([
        'metadata' => ['nearest_landmark' => 'main'],
    ]);

    expect($event->metadata)->toBeInstanceOf(InsightsEventMetadataData::class)
        ->and($event->metadata->nearestLandmark)->toBe('main');
});

it('carries the full journey step shape', function (): void {
    $occurredAt = CarbonImmutable::parse('2026-04-30 12:00:00');

    $data = InsightsJourneyStepData::from([
        'sequence' => 2,
        'type' => 'click',
        'url' => 'https://example.test/path',
        'path' => '/path',
        'title' => 'Example',
        'event_name' => 'cta_click',
        'label' => 'Book a demo',
        'location' => 'home.hero',
        'occurred_at' => $occurredAt,
        'seconds_since_previous_step' => 15,
    ]);

    expect($data->sequence)->toBe(2)
        ->and($data->type)->toBe(InsightsEventType::Click)
        ->and($data->url)->toBe('https://example.test/path')
        ->and($data->path)->toBe('/path')
        ->and($data->title)->toBe('Example')
        ->and($data->eventName)->toBe('cta_click')
        ->and($data->label)->toBe('Book a demo')
        ->and($data->location)->toBe('home.hero')
        ->and($data->occurredAt)->toEqual($occurredAt)
        ->and($data->secondsSincePreviousStep)->toBe(15);
});
