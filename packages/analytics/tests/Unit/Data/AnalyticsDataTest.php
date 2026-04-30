<?php

declare(strict_types=1);

use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsConsentCategory;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Tests\AnalyticsTestCase;

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
        ->and($data->path())->toBe('/path');
});
