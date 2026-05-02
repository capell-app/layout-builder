<?php

declare(strict_types=1);

use Capell\Analytics\Actions\RecordAnalyticsEventAction;
use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;

it('skips events when the package is disabled', function (): void {
    config()->set('capell-analytics.enabled', false);

    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
    ]);

    $event = RecordAnalyticsEventAction::run($visit->uuid, analyticsEventData());

    expect($event)->toBeNull()
        ->and(AnalyticsEvent::query()->count())->toBe(0);
});

it('skips uk or europe events without analytics consent', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::UkOrEurope,
        'consent_status' => AnalyticsConsentStatus::RejectedNonEssential,
    ]);

    $event = RecordAnalyticsEventAction::run($visit->uuid, analyticsEventData());

    expect($event)->toBeNull()
        ->and(AnalyticsEvent::query()->count())->toBe(0);
});

it('stores granular uk or europe events when latest consent allows analytics', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::UkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Granular,
    ]);

    AnalyticsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'categories' => new AnalyticsConsentData(analytics: true),
        'decided_at' => now()->subMinute()->toImmutable(),
    ]);

    $event = RecordAnalyticsEventAction::run($visit->uuid, analyticsEventData());

    expect($event)->toBeInstanceOf(AnalyticsEvent::class)
        ->and($event?->visit_id)->toBe($visit->getKey());
});

it('skips uk or europe events when the latest consent revokes analytics', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::UkOrEurope,
        'consent_status' => AnalyticsConsentStatus::RejectedNonEssential,
    ]);

    AnalyticsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => AnalyticsConsentStatus::Granular,
        'categories' => new AnalyticsConsentData(analytics: true),
        'decided_at' => now()->subMinute()->toImmutable(),
    ]);

    AnalyticsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => AnalyticsConsentStatus::RejectedNonEssential,
        'categories' => new AnalyticsConsentData(analytics: false),
        'decided_at' => now()->toImmutable(),
    ]);

    $event = RecordAnalyticsEventAction::run($visit->uuid, analyticsEventData());

    expect($event)->toBeNull()
        ->and(AnalyticsEvent::query()->count())->toBe(0);
});

it('requires consent for outside-region events when configured globally', function (): void {
    config()->set('capell-analytics.require_consent_for_all_regions', true);

    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $event = RecordAnalyticsEventAction::run($visit->uuid, analyticsEventData());

    expect($event)->toBeNull()
        ->and(AnalyticsEvent::query()->count())->toBe(0);
});

it('assigns the next sequence for a visit', function (): void {
    $visit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::OutsideUkOrEurope,
    ]);

    AnalyticsEvent::factory()->create([
        'visit_id' => $visit->getKey(),
        'sequence' => 3,
    ]);

    $event = RecordAnalyticsEventAction::run($visit->uuid, analyticsEventData());

    expect($event?->sequence)->toBe(4);
});

function analyticsEventData(): AnalyticsEventData
{
    return new AnalyticsEventData(
        type: AnalyticsEventType::PageView,
        url: 'https://example.test/',
        title: 'Home',
    );
}
