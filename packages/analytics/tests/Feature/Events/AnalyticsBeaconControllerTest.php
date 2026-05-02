<?php

declare(strict_types=1);

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;

it('does not store a uk or europe event without analytics consent', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::UkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit))
        ->assertNoContent();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('does not store events after uk or europe analytics consent is revoked', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::UkOrEurope,
        'consent_status' => AnalyticsConsentStatus::RejectedNonEssential,
    ]);

    AnalyticsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => AnalyticsConsentStatus::Granular,
        'categories' => [
            'essential' => true,
            'analytics' => true,
            'marketing' => false,
            'preferences' => false,
        ],
        'decided_at' => now()->subMinute()->toImmutable(),
    ]);

    AnalyticsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => AnalyticsConsentStatus::RejectedNonEssential,
        'categories' => [
            'essential' => true,
            'analytics' => false,
            'marketing' => false,
            'preferences' => false,
        ],
        'decided_at' => now()->toImmutable(),
    ]);

    $this->postJson(route('capell-analytics.events'), [
        'visit_id' => $visit->uuid,
        'events' => [
            pageViewEvent(),
            clickEvent(),
        ],
    ])->assertNoContent();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('stores a page view after analytics consent is granted', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::UkOrEurope,
        'consent_status' => AnalyticsConsentStatus::AcceptedAll,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit))
        ->assertNoContent();

    $event = AnalyticsEvent::query()->firstOrFail();

    expect($event->visit_id)->toBe($visit->getKey())
        ->and($event->type)->toBe(AnalyticsEventType::PageView)
        ->and($event->url)->toBe('https://example.test/')
        ->and($event->path)->toBe('/')
        ->and($event->sequence)->toBe(1);
});

it('stores an outside-region page view with default settings', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit))
        ->assertNoContent();

    expect(AnalyticsEvent::query()->count())->toBe(1);
});

it('stores click location fields', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), clickPayload($visit))
        ->assertNoContent();

    $event = AnalyticsEvent::query()->firstOrFail();

    expect($event->type)->toBe(AnalyticsEventType::Click)
        ->and($event->event_name)->toBe('cta_click')
        ->and($event->label)->toBe('Book a demo')
        ->and($event->location)->toBe('home.hero')
        ->and($event->target_selector)->toBe('button[data-capell-analytics]')
        ->and($event->viewport_x)->toBe(24)
        ->and($event->viewport_y)->toBe(50)
        ->and($event->document_x)->toBe(24)
        ->and($event->document_y)->toBe(650)
        ->and($event->metadata->nearestLandmark)->toBe('main');
});

it('skips events on ignored paths', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit, [
        'url' => 'https://example.test/admin/pages',
    ]))->assertNoContent();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('returns unprocessable for invalid event type', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit, [
        'type' => 'not-real',
    ]))->assertUnprocessable();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('returns unprocessable for overlong urls before persistence', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit, [
        'url' => 'https://example.test/' . str_repeat('a', 512),
    ]))->assertUnprocessable();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('returns unprocessable for arbitrary nested metadata', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), clickPayload($visit, [
        'metadata' => [
            'nearest_landmark' => 'main',
            'attributes' => [
                'nested' => true,
            ],
        ],
    ]))->assertUnprocessable();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('returns no content for successful beacon posts', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-analytics.events'), pageViewPayload($visit))
        ->assertStatus(204)
        ->assertNoContent();
});

it('does not require a csrf token for beacon posts', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $this->post(route('capell-analytics.events'), pageViewPayload($visit))
        ->assertStatus(204)
        ->assertNoContent();
});

/**
 * @param  array<string, mixed>  $eventOverrides
 * @return array<string, mixed>
 */
function pageViewPayload(AnalyticsVisit $visit, array $eventOverrides = []): array
{
    return [
        'visit_id' => $visit->uuid,
        'events' => [
            pageViewEvent($eventOverrides),
        ],
    ];
}

/**
 * @param  array<string, mixed>  $eventOverrides
 * @return array<string, mixed>
 */
function clickPayload(AnalyticsVisit $visit, array $eventOverrides = []): array
{
    return [
        'visit_id' => $visit->uuid,
        'events' => [
            clickEvent($eventOverrides),
        ],
    ];
}

/**
 * @param  array<string, mixed>  $eventOverrides
 * @return array<string, mixed>
 */
function pageViewEvent(array $eventOverrides = []): array
{
    return array_merge([
        'type' => AnalyticsEventType::PageView->value,
        'url' => 'https://example.test/',
        'title' => 'Home',
        'occurred_at' => now()->toIso8601String(),
    ], $eventOverrides);
}

/**
 * @param  array<string, mixed>  $eventOverrides
 * @return array<string, mixed>
 */
function clickEvent(array $eventOverrides = []): array
{
    return array_merge([
        'type' => 'click',
        'url' => 'https://example.test/',
        'title' => 'Home',
        'occurred_at' => now()->toIso8601String(),
        'event_name' => 'cta_click',
        'label' => 'Book a demo',
        'location' => 'home.hero',
        'target_selector' => 'button[data-capell-analytics]',
        'viewport_x' => 24,
        'viewport_y' => 50,
        'document_x' => 24,
        'document_y' => 650,
        'metadata' => ['nearest_landmark' => 'main'],
    ], $eventOverrides);
}
