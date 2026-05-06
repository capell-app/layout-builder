<?php

declare(strict_types=1);

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsVisit;
use Carbon\CarbonImmutable;

it('rejects granular consent without accepted terms', function (): void {
    $this->postJson(route('capell-insights.consent'), [
        'region' => InsightsConsentRegion::UkOrEurope->value,
        'status' => InsightsConsentStatus::Granular->value,
        'categories' => [
            'insights' => true,
            'marketing' => false,
            'preferences' => false,
        ],
    ])->assertUnprocessable();
});

it('rejects pending as a submitted consent decision', function (): void {
    $this->postJson(route('capell-insights.consent'), [
        'region' => InsightsConsentRegion::UkOrEurope->value,
        'status' => InsightsConsentStatus::Pending->value,
    ])->assertUnprocessable();

    expect(InsightsConsent::query()->count())->toBe(0)
        ->and(InsightsVisit::query()->count())->toBe(0);
});

it('stores uk or europe granular consent categories and visit row', function (): void {
    $response = $this->postJson(route('capell-insights.consent'), [
        'region' => InsightsConsentRegion::UkOrEurope->value,
        'status' => InsightsConsentStatus::Granular->value,
        'terms_accepted' => true,
        'categories' => [
            'insights' => true,
            'marketing' => false,
            'preferences' => false,
        ],
    ]);

    $response->assertOk()
        ->assertCookie('capell_insights_visit')
        ->assertJsonPath('enabled_categories', ['essential', 'insights'])
        ->assertJsonStructure(['visit_id', 'enabled_categories']);

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = InsightsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = InsightsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->consent_region)->toBe(InsightsConsentRegion::UkOrEurope)
        ->and($visit->consent_status)->toBe(InsightsConsentStatus::Granular)
        ->and($consent->consent_region)->toBe(InsightsConsentRegion::UkOrEurope)
        ->and($consent->status)->toBe(InsightsConsentStatus::Granular)
        ->and($consent->categories->enabledCategories())->toHaveCount(2)
        ->and($consent->categories->insights)->toBeTrue()
        ->and($consent->categories->marketing)->toBeFalse()
        ->and($consent->categories->preferences)->toBeFalse()
        ->and($consent->terms_accepted_at)->not->toBeNull();
});

it('stores essential only categories when non-essential consent is rejected', function (): void {
    $response = $this->postJson(route('capell-insights.consent'), [
        'region' => InsightsConsentRegion::OutsideUkOrEurope->value,
        'status' => InsightsConsentStatus::RejectedNonEssential->value,
        'categories' => [
            'insights' => true,
            'marketing' => true,
            'preferences' => true,
        ],
    ]);

    $response->assertOk()
        ->assertCookie('capell_insights_visit')
        ->assertJsonPath('enabled_categories', ['essential'])
        ->assertJsonStructure(['visit_id', 'enabled_categories']);

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = InsightsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = InsightsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->consent_status)->toBe(InsightsConsentStatus::RejectedNonEssential)
        ->and($consent->categories->enabledCategories())->toHaveCount(1)
        ->and($consent->categories->insights)->toBeFalse()
        ->and($consent->categories->marketing)->toBeFalse()
        ->and($consent->categories->preferences)->toBeFalse();
});

it('stores all non-essential categories when all consent is accepted', function (): void {
    $response = $this->postJson(route('capell-insights.consent'), [
        'region' => InsightsConsentRegion::UkOrEurope->value,
        'status' => InsightsConsentStatus::AcceptedAll->value,
        'categories' => [
            'insights' => false,
            'marketing' => false,
            'preferences' => false,
        ],
    ]);

    $response->assertOk()
        ->assertCookie('capell_insights_visit')
        ->assertJsonPath('enabled_categories', ['essential', 'insights', 'marketing', 'preferences']);

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = InsightsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = InsightsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->consent_status)->toBe(InsightsConsentStatus::AcceptedAll)
        ->and($consent->status)->toBe(InsightsConsentStatus::AcceptedAll)
        ->and($consent->categories->insights)->toBeTrue()
        ->and($consent->categories->marketing)->toBeTrue()
        ->and($consent->categories->preferences)->toBeTrue();
});

it('reuses an existing visit when the insights visit cookie is present', function (): void {
    $existingVisit = InsightsVisit::factory()->create([
        'consent_region' => InsightsConsentRegion::Unknown,
        'consent_status' => InsightsConsentStatus::Pending,
    ]);

    $response = $this
        ->withCredentials()
        ->withCookie('capell_insights_visit', $existingVisit->uuid)
        ->postJson(route('capell-insights.consent'), [
            'region' => InsightsConsentRegion::UkOrEurope->value,
            'status' => InsightsConsentStatus::RejectedNonEssential->value,
        ]);

    $response->assertOk()
        ->assertJsonPath('visit_id', $existingVisit->uuid)
        ->assertCookie('capell_insights_visit');

    $existingVisit->refresh();

    expect(InsightsVisit::query()->count())->toBe(1)
        ->and($existingVisit->consent_region)->toBe(InsightsConsentRegion::UkOrEurope)
        ->and($existingVisit->consent_status)->toBe(InsightsConsentStatus::RejectedNonEssential)
        ->and(InsightsConsent::query()->where('visit_id', $existingVisit->getKey())->exists())->toBeTrue();
});

it('stores hmac visitor hashes when visitor data hashing is enabled', function (): void {
    config()->set('capell-insights.hash_visitor_data', true);
    config()->set('capell-insights.hash_salt', 'insights-test-salt');

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_USER_AGENT' => 'Capell Consent Test Browser',
        ])
        ->postJson(route('capell-insights.consent'), [
            'region' => InsightsConsentRegion::UkOrEurope->value,
            'status' => InsightsConsentStatus::RejectedNonEssential->value,
        ]);

    $response->assertOk();

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = InsightsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = InsightsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    $expectedIpHash = hash_hmac('sha256', '203.0.113.50', 'insights-test-salt');
    $expectedUserAgentHash = hash_hmac('sha256', 'Capell Consent Test Browser', 'insights-test-salt');

    expect($visit->ip_hash)->toBe($expectedIpHash)
        ->and($visit->user_agent_hash)->toBe($expectedUserAgentHash)
        ->and($consent->ip_hash)->toBe($expectedIpHash)
        ->and($consent->user_agent_hash)->toBe($expectedUserAgentHash);
});

it('stores null visitor hashes when visitor data hashing is disabled', function (): void {
    config()->set('capell-insights.hash_visitor_data', false);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.60',
            'HTTP_USER_AGENT' => 'Capell Consent Test Browser',
        ])
        ->postJson(route('capell-insights.consent'), [
            'region' => InsightsConsentRegion::UkOrEurope->value,
            'status' => InsightsConsentStatus::RejectedNonEssential->value,
        ]);

    $response->assertOk();

    /** @var string $visitUuid */
    $visitUuid = $response->json('visit_id');

    $visit = InsightsVisit::query()
        ->where('uuid', $visitUuid)
        ->firstOrFail();

    $consent = InsightsConsent::query()
        ->where('visit_id', $visit->getKey())
        ->firstOrFail();

    expect($visit->ip_hash)->toBeNull()
        ->and($visit->user_agent_hash)->toBeNull()
        ->and($consent->ip_hash)->toBeNull()
        ->and($consent->user_agent_hash)->toBeNull();
});

it('queues the insights visit cookie for one year', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-30 12:00:00'));

    $response = $this->postJson(route('capell-insights.consent'), [
        'region' => InsightsConsentRegion::UkOrEurope->value,
        'status' => InsightsConsentStatus::RejectedNonEssential->value,
    ]);

    $response->assertOk()
        ->assertCookie('capell_insights_visit');

    $visitCookie = $response->getCookie('capell_insights_visit', false);

    expect($visitCookie)->not->toBeNull()
        ->and($visitCookie?->getExpiresTime())->toBe(CarbonImmutable::now()->addYear()->getTimestamp());

    CarbonImmutable::setTestNow();
});
