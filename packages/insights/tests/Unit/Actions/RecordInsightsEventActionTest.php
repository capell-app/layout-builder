<?php

declare(strict_types=1);

use Capell\Insights\Actions\RecordInsightsEventAction;
use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;

it('skips events when the package is disabled', function (): void {
    config()->set('capell-insights.enabled', false);

    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
    ]);

    $event = RecordInsightsEventAction::run($visit->uuid, insightsEventData());

    expect($event)->toBeNull()
        ->and(InsightsEvent::query()->count())->toBe(0);
});

it('skips uk or europe events without insights consent', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::UkOrEurope,
        'consent_status' => InsightsConsentStatus::RejectedNonEssential,
    ]);

    $event = RecordInsightsEventAction::run($visit->uuid, insightsEventData());

    expect($event)->toBeNull()
        ->and(InsightsEvent::query()->count())->toBe(0);
});

it('stores granular uk or europe events when latest consent allows insights', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::UkOrEurope,
        'consent_status' => InsightsConsentStatus::Granular,
    ]);

    InsightsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'categories' => new InsightsConsentData(insights: true),
        'decided_at' => now()->subMinute()->toImmutable(),
    ]);

    $event = RecordInsightsEventAction::run($visit->uuid, insightsEventData());

    expect($event)->toBeInstanceOf(InsightsEvent::class)
        ->and($event?->visit_id)->toBe($visit->getKey());
});

it('skips uk or europe events when the latest consent revokes insights', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::UkOrEurope,
        'consent_status' => InsightsConsentStatus::RejectedNonEssential,
    ]);

    InsightsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => InsightsConsentStatus::Granular,
        'categories' => new InsightsConsentData(insights: true),
        'decided_at' => now()->subMinute()->toImmutable(),
    ]);

    InsightsConsent::factory()->create([
        'visit_id' => $visit->getKey(),
        'status' => InsightsConsentStatus::RejectedNonEssential,
        'categories' => new InsightsConsentData(insights: false),
        'decided_at' => now()->toImmutable(),
    ]);

    $event = RecordInsightsEventAction::run($visit->uuid, insightsEventData());

    expect($event)->toBeNull()
        ->and(InsightsEvent::query()->count())->toBe(0);
});

it('requires consent for outside-region events when configured globally', function (): void {
    config()->set('capell-insights.require_consent_for_all_regions', true);

    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $event = RecordInsightsEventAction::run($visit->uuid, insightsEventData());

    expect($event)->toBeNull()
        ->and(InsightsEvent::query()->count())->toBe(0);
});

it('assigns the next sequence for a visit', function (): void {
    $visit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::OutsideUkOrEurope,
    ]);

    InsightsEvent::factory()->create([
        'visit_id' => $visit->getKey(),
        'sequence' => 3,
    ]);

    $event = RecordInsightsEventAction::run($visit->uuid, insightsEventData());

    expect($event?->sequence)->toBe(4);
});

function insightsEventData(): InsightsEventData
{
    return new InsightsEventData(
        type: InsightsEventType::PageView,
        url: 'https://example.test/',
        title: 'Home',
    );
}
