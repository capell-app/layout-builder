<?php

declare(strict_types=1);

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;

it('does not store a uk or europe event without insights consent', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::UkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit))
        ->assertNoContent();

    expect(InsightsEvent::query()->count())->toBe(0);
});

it('does not store events after uk or europe insights consent is revoked', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::UkOrEurope,
        'consent_status' => InsightsConsentStatus::RejectedNonEssential,
    ]);

    InsightsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => InsightsConsentStatus::Granular,
        'categories' => [
            'essential' => true,
            'insights' => true,
            'marketing' => false,
            'preferences' => false,
        ],
        'decided_at' => now()->subMinute()->toImmutable(),
    ]);

    InsightsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => InsightsConsentStatus::RejectedNonEssential,
        'categories' => [
            'essential' => true,
            'insights' => false,
            'marketing' => false,
            'preferences' => false,
        ],
        'decided_at' => now()->toImmutable(),
    ]);

    $this->postJson(route('capell-insights.events'), [
        'visit_id' => $visit->uuid,
        'events' => [
            pageViewEvent(),
            clickEvent(),
        ],
    ])->assertNoContent();

    expect(InsightsEvent::query()->count())->toBe(0);
});

it('stores a page view after insights consent is granted', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::UkOrEurope,
        'consent_status' => InsightsConsentStatus::AcceptedAll,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit))
        ->assertNoContent();

    $event = InsightsEvent::query()->firstOrFail();

    expect($event->visit_id)->toBe($visit->getKey())
        ->and($event->type)->toBe(InsightsEventType::PageView)
        ->and($event->url)->toBe('https://example.test/')
        ->and($event->path)->toBe('/')
        ->and($event->sequence)->toBe(1);
});

it('stores a mixed event batch with one visit lookup and sequential events', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), [
        'visit_id' => $visit->uuid,
        'events' => [
            pageViewEvent(['url' => 'https://example.test/first']),
            clickEvent(['url' => 'https://example.test/first']),
            pageViewEvent(['url' => 'https://example.test/admin/pages']),
            pageViewEvent(['url' => 'https://example.test/asset.css']),
        ],
    ])->assertNoContent();

    $events = InsightsEvent::query()->orderBy('sequence')->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('sequence')->all())->toBe([1, 2])
        ->and($events->pluck('path')->all())->toBe(['/first', '/first'])
        ->and($visit->refresh()->last_seen_at)->not->toBeNull();
});

it('stores an outside-region page view with default settings', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit))
        ->assertNoContent();

    expect(InsightsEvent::query()->count())->toBe(1);
});

it('stores click location fields', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), clickPayload($visit))
        ->assertNoContent();

    $event = InsightsEvent::query()->firstOrFail();

    expect($event->type)->toBe(InsightsEventType::Click)
        ->and($event->event_name)->toBe('cta_click')
        ->and($event->label)->toBe('Book a demo')
        ->and($event->location)->toBe('home.hero')
        ->and($event->target_selector)->toBe('button[data-capell-insights]')
        ->and($event->viewport_x)->toBe(24)
        ->and($event->viewport_y)->toBe(50)
        ->and($event->document_x)->toBe(24)
        ->and($event->document_y)->toBe(650)
        ->and($event->metadata->nearestLandmark)->toBe('main');
});

it('skips events on ignored paths', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit, [
        'url' => 'https://example.test/admin/pages',
    ]))->assertNoContent();

    expect(InsightsEvent::query()->count())->toBe(0);
});

it('skips admin livewire beacon and asset paths', function (string $url): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit, [
        'url' => $url,
    ]))->assertNoContent();

    expect(InsightsEvent::query()->count())->toBe(0);
})->with([
    'https://example.test/admin/login',
    'https://example.test/livewire/update',
    'https://example.test/capell/insights/events',
    'https://example.test/app.js',
]);

it('returns unprocessable for invalid event type', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit, [
        'type' => 'not-real',
    ]))->assertUnprocessable();

    expect(InsightsEvent::query()->count())->toBe(0);
});

it('returns unprocessable for overlong urls before persistence', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit, [
        'url' => 'https://example.test/' . str_repeat('a', 512),
    ]))->assertUnprocessable();

    expect(InsightsEvent::query()->count())->toBe(0);
});

it('returns unprocessable for arbitrary nested metadata', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), clickPayload($visit, [
        'metadata' => [
            'nearest_landmark' => 'main',
            'attributes' => [
                'nested' => true,
            ],
        ],
    ]))->assertUnprocessable();

    expect(InsightsEvent::query()->count())->toBe(0);
});

it('returns no content for successful beacon posts', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->postJson(route('capell-insights.events'), pageViewPayload($visit))
        ->assertStatus(204)
        ->assertNoContent();
});

it('does not require a csrf token for beacon posts', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $this->post(route('capell-insights.events'), pageViewPayload($visit))
        ->assertStatus(204)
        ->assertNoContent();
});

/**
 * @param  array<string, mixed>  $eventOverrides
 * @return array<string, mixed>
 */
function pageViewPayload(InsightsVisit $visit, array $eventOverrides = []): array
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
function clickPayload(InsightsVisit $visit, array $eventOverrides = []): array
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
        'type' => InsightsEventType::PageView->value,
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
        'target_selector' => 'button[data-capell-insights]',
        'viewport_x' => 24,
        'viewport_y' => 50,
        'document_x' => 24,
        'document_y' => 650,
        'metadata' => ['nearest_landmark' => 'main'],
    ], $eventOverrides);
}
