<?php

declare(strict_types=1);

use Capell\Insights\Actions\ResolveConsentRegionAction;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Support\Consent\ConsentRegionResolver;

it('returns forced uk or europe consent region from config', function (): void {
    config()->set('capell-insights.default_consent_region', 'uk_or_europe');

    expect(ResolveConsentRegionAction::run())->toBe(InsightsConsentRegion::UkOrEurope);
});

it('returns forced outside uk or europe consent region from config', function (): void {
    config()->set('capell-insights.default_consent_region', 'outside_uk_or_europe');

    expect(ResolveConsentRegionAction::run())->toBe(InsightsConsentRegion::OutsideUkOrEurope);
});

it('returns unknown when location is invalid or missing', function (): void {
    config()->set('capell-insights.default_consent_region');

    $resolver = resolve(ConsentRegionResolver::class);

    expect(ResolveConsentRegionAction::run())->toBe(InsightsConsentRegion::Unknown)
        ->and($resolver->resolveFromLocation(null))->toBe(InsightsConsentRegion::Unknown)
        ->and($resolver->resolveFromLocation(['country' => null]))->toBe(InsightsConsentRegion::Unknown);
});

it('maps uk and europe country codes to uk or europe consent region', function (string $countryCode): void {
    config()->set('capell-insights.default_consent_region');

    $resolver = resolve(ConsentRegionResolver::class);

    expect($resolver->resolveFromLocation(['iso_code' => $countryCode]))
        ->toBe(InsightsConsentRegion::UkOrEurope);
})->with(['GB', 'FR', 'NO', 'CH']);

it('maps non-listed country codes to outside uk or europe consent region', function (): void {
    config()->set('capell-insights.default_consent_region');

    $resolver = resolve(ConsentRegionResolver::class);

    expect($resolver->resolveFromLocation(['iso_code' => 'US']))
        ->toBe(InsightsConsentRegion::OutsideUkOrEurope);
});
