<?php

declare(strict_types=1);

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsVisit;
use Carbon\CarbonImmutable;

it('rejects granular consent without accepted terms', function (): void {
    $this->postJson(route('capell-analytics.consent'), [
        'region' => AnalyticsConsentRegion::UkOrEurope->value,
        'status' => AnalyticsConsentStatus::Granular->value,
        'categories' => [
            'analytics' => true,
            'marketing' => false,
            'preferences' => false,
        ],
    ])->assertUnprocessable();
});

it('rejects pending as a submitted consent decision', function (): void {
    $this->postJson(route('capell-analytics.consent'), [
        'region' => AnalyticsConsentRegion::UkOrEurope->value,
        'status' => AnalyticsConsentStatus::Pending->value,
    ])->assertUnprocessable();

    expect(AnalyticsConsent::query()->count())->toBe(0)
        ->and(AnalyticsVisit::query()->count())->toBe(0);
});

it('stores uk or europe granular consent categories and visit row', function (): void {
    $response = $this->postJson(route('capell-analytics.consent'), [
        'region' => AnalyticsConsentRegion::UkOrEurope->value,
        'status' => AnalyticsConsentStatus::Granular->value,
        'terms_accepted' => true,
        'categories' => [
            'analytics' => true,
            'marketing' => false,
            'preferences' => false,
        ],
    ]);

    $response->assertOk()
        ->assertCookie('capell_analytics_visit')
        ->assertJsonPath('enabled_categories', ['essential', 'analytics'])
        ->assertJsonStructure(['visit_id', 'enabled_categories']);

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = AnalyticsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = AnalyticsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->consent_region)->toBe(AnalyticsConsentRegion::UkOrEurope)
        ->and($visit->consent_status)->toBe(AnalyticsConsentStatus::Granular)
        ->and($consent->consent_region)->toBe(AnalyticsConsentRegion::UkOrEurope)
        ->and($consent->status)->toBe(AnalyticsConsentStatus::Granular)
        ->and($consent->categories->enabledCategories())->toHaveCount(2)
        ->and($consent->categories->analytics)->toBeTrue()
        ->and($consent->categories->marketing)->toBeFalse()
        ->and($consent->categories->preferences)->toBeFalse()
        ->and($consent->terms_accepted_at)->not->toBeNull();
});

it('stores essential only categories when non-essential consent is rejected', function (): void {
    $response = $this->postJson(route('capell-analytics.consent'), [
        'region' => AnalyticsConsentRegion::OutsideUkOrEurope->value,
        'status' => AnalyticsConsentStatus::RejectedNonEssential->value,
        'categories' => [
            'analytics' => true,
            'marketing' => true,
            'preferences' => true,
        ],
    ]);

    $response->assertOk()
        ->assertCookie('capell_analytics_visit')
        ->assertJsonPath('enabled_categories', ['essential'])
        ->assertJsonStructure(['visit_id', 'enabled_categories']);

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = AnalyticsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = AnalyticsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->consent_status)->toBe(AnalyticsConsentStatus::RejectedNonEssential)
        ->and($consent->categories->enabledCategories())->toHaveCount(1)
        ->and($consent->categories->analytics)->toBeFalse()
        ->and($consent->categories->marketing)->toBeFalse()
        ->and($consent->categories->preferences)->toBeFalse();
});

it('stores all non-essential categories when all consent is accepted', function (): void {
    $response = $this->postJson(route('capell-analytics.consent'), [
        'region' => AnalyticsConsentRegion::UkOrEurope->value,
        'status' => AnalyticsConsentStatus::AcceptedAll->value,
        'categories' => [
            'analytics' => false,
            'marketing' => false,
            'preferences' => false,
        ],
    ]);

    $response->assertOk()
        ->assertCookie('capell_analytics_visit')
        ->assertJsonPath('enabled_categories', ['essential', 'analytics', 'marketing', 'preferences']);

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = AnalyticsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = AnalyticsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->consent_status)->toBe(AnalyticsConsentStatus::AcceptedAll)
        ->and($consent->status)->toBe(AnalyticsConsentStatus::AcceptedAll)
        ->and($consent->categories->analytics)->toBeTrue()
        ->and($consent->categories->marketing)->toBeTrue()
        ->and($consent->categories->preferences)->toBeTrue();
});

it('reuses an existing visit when the analytics visit cookie is present', function (): void {
    $existingVisit = AnalyticsVisit::factory()->create([
        'consent_region' => AnalyticsConsentRegion::Unknown,
        'consent_status' => AnalyticsConsentStatus::Pending,
    ]);

    $response = $this
        ->withCredentials()
        ->withCookie('capell_analytics_visit', $existingVisit->uuid)
        ->postJson(route('capell-analytics.consent'), [
            'region' => AnalyticsConsentRegion::UkOrEurope->value,
            'status' => AnalyticsConsentStatus::RejectedNonEssential->value,
        ]);

    $response->assertOk()
        ->assertJsonPath('visit_id', $existingVisit->uuid)
        ->assertCookie('capell_analytics_visit');

    $existingVisit->refresh();

    expect(AnalyticsVisit::query()->count())->toBe(1)
        ->and($existingVisit->consent_region)->toBe(AnalyticsConsentRegion::UkOrEurope)
        ->and($existingVisit->consent_status)->toBe(AnalyticsConsentStatus::RejectedNonEssential)
        ->and(AnalyticsConsent::query()->where('visit_id', $existingVisit->getKey())->exists())->toBeTrue();
});

it('stores hmac visitor hashes when visitor data hashing is enabled', function (): void {
    config()->set('capell-analytics.hash_visitor_data', true);
    config()->set('capell-analytics.hash_salt', 'analytics-test-salt');

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_USER_AGENT' => 'Capell Consent Test Browser',
        ])
        ->postJson(route('capell-analytics.consent'), [
            'region' => AnalyticsConsentRegion::UkOrEurope->value,
            'status' => AnalyticsConsentStatus::RejectedNonEssential->value,
        ]);

    $response->assertOk();

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = AnalyticsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = AnalyticsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    $expectedIpHash = hash_hmac('sha256', '203.0.113.50', 'analytics-test-salt');
    $expectedUserAgentHash = hash_hmac('sha256', 'Capell Consent Test Browser', 'analytics-test-salt');

    expect($visit->ip_hash)->toBe($expectedIpHash)
        ->and($visit->user_agent_hash)->toBe($expectedUserAgentHash)
        ->and($consent->ip_hash)->toBe($expectedIpHash)
        ->and($consent->user_agent_hash)->toBe($expectedUserAgentHash);
});

it('stores null visitor hashes when visitor data hashing is disabled', function (): void {
    config()->set('capell-analytics.hash_visitor_data', false);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.60',
            'HTTP_USER_AGENT' => 'Capell Consent Test Browser',
        ])
        ->postJson(route('capell-analytics.consent'), [
            'region' => AnalyticsConsentRegion::UkOrEurope->value,
            'status' => AnalyticsConsentStatus::RejectedNonEssential->value,
        ]);

    $response->assertOk();

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = AnalyticsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = AnalyticsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->ip_hash)->toBeNull()
        ->and($visit->user_agent_hash)->toBeNull()
        ->and($consent->ip_hash)->toBeNull()
        ->and($consent->user_agent_hash)->toBeNull();
});

it('queues the analytics visit cookie for one year', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-30 12:00:00'));

    $response = $this->postJson(route('capell-analytics.consent'), [
        'region' => AnalyticsConsentRegion::UkOrEurope->value,
        'status' => AnalyticsConsentStatus::RejectedNonEssential->value,
    ]);

    $response->assertOk()
        ->assertCookie('capell_analytics_visit');

    $visitCookie = $response->getCookie('capell_analytics_visit', false);

    expect($visitCookie)->not->toBeNull()
        ->and($visitCookie?->getExpiresTime())->toBe(CarbonImmutable::now()->addYear()->getTimestamp());

    CarbonImmutable::setTestNow();
});
