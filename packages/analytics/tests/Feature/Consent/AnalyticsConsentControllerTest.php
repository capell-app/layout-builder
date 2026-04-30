<?php

declare(strict_types=1);

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Tests\AnalyticsTestCase;

uses(AnalyticsTestCase::class);

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
