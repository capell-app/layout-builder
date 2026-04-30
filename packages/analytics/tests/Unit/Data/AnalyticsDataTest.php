<?php

declare(strict_types=1);

use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Data\AnalyticsEventMetadataData;
use Capell\Analytics\Data\AnalyticsJourneyStepData;
use Capell\Analytics\Enums\AnalyticsConsentCategory;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Carbon\CarbonImmutable;

uses(AnalyticsTestCase::class);

it('serializes consent categories as data', function (): void {
    $data = AnalyticsConsentData::from([
        'essential' => true,
        'analytics' => true,
        'marketing' => false,
        'preferences' => false,
    ]);

    expect($data->enabledCategories())->toBe([
        AnalyticsConsentCategory::Essential,
        AnalyticsConsentCategory::Analytics,
    ]);
});

it('always treats essential consent as enabled', function (): void {
    $data = AnalyticsConsentData::from([
        'essential' => false,
        'analytics' => false,
        'marketing' => false,
        'preferences' => false,
    ]);

    expect($data->enabledCategories())->toBe([
        AnalyticsConsentCategory::Essential,
    ]);
});

it('normalizes event data', function (): void {
    $data = AnalyticsEventData::from([
        'type' => 'click',
        'url' => 'https://example.test/path?token=secret',
        'title' => 'Example',
        'event_name' => 'cta_click',
        'label' => 'Book a demo',
        'location' => 'home.hero',
        'target_selector' => 'button[data-capell-analytics]',
        'viewport_x' => 10,
        'viewport_y' => 20,
        'document_x' => 10,
        'document_y' => 520,
        'metadata' => ['nearest_landmark' => 'main'],
    ]);

    expect($data->type)->toBe(AnalyticsEventType::Click)
        ->and($data->path())->toBe('/path')
        ->and($data->metadata)->toBeInstanceOf(AnalyticsEventMetadataData::class)
        ->and($data->metadata?->nearestLandmark)->toBe('main');
});

it('casts event model metadata as data', function (): void {
    $event = new AnalyticsEvent([
        'metadata' => ['nearest_landmark' => 'main'],
    ]);

    expect($event->metadata)->toBeInstanceOf(AnalyticsEventMetadataData::class)
        ->and($event->metadata->nearestLandmark)->toBe('main');
});

it('carries the full journey step shape', function (): void {
    $occurredAt = CarbonImmutable::parse('2026-04-30 12:00:00');

    $data = AnalyticsJourneyStepData::from([
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
        ->and($data->type)->toBe(AnalyticsEventType::Click)
        ->and($data->url)->toBe('https://example.test/path')
        ->and($data->path)->toBe('/path')
        ->and($data->title)->toBe('Example')
        ->and($data->eventName)->toBe('cta_click')
        ->and($data->label)->toBe('Book a demo')
        ->and($data->location)->toBe('home.hero')
        ->and($data->occurredAt)->toEqual($occurredAt)
        ->and($data->secondsSincePreviousStep)->toBe(15);
});
