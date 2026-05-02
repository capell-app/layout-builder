<?php

declare(strict_types=1);

use Capell\Analytics\Actions\ResolveConsentRegionAction;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Support\Consent\ConsentRegionResolver;

it('returns forced uk or europe consent region from config', function (): void {
    config()->set('capell-analytics.default_consent_region', 'uk_or_europe');

    expect(ResolveConsentRegionAction::run())->toBe(AnalyticsConsentRegion::UkOrEurope);
});

it('returns forced outside uk or europe consent region from config', function (): void {
    config()->set('capell-analytics.default_consent_region', 'outside_uk_or_europe');

    expect(ResolveConsentRegionAction::run())->toBe(AnalyticsConsentRegion::OutsideUkOrEurope);
});

it('returns unknown when location is invalid or missing', function (): void {
    config()->set('capell-analytics.default_consent_region');

    $resolver = resolve(ConsentRegionResolver::class);

    expect(ResolveConsentRegionAction::run())->toBe(AnalyticsConsentRegion::Unknown)
        ->and($resolver->resolveFromLocation(null))->toBe(AnalyticsConsentRegion::Unknown)
        ->and($resolver->resolveFromLocation(['country' => null]))->toBe(AnalyticsConsentRegion::Unknown);
});

it('maps uk and europe country codes to uk or europe consent region', function (string $countryCode): void {
    config()->set('capell-analytics.default_consent_region');

    $resolver = resolve(ConsentRegionResolver::class);

    expect($resolver->resolveFromLocation(['iso_code' => $countryCode]))
        ->toBe(AnalyticsConsentRegion::UkOrEurope);
})->with(['GB', 'FR', 'NO', 'CH']);

it('maps non-listed country codes to outside uk or europe consent region', function (): void {
    config()->set('capell-analytics.default_consent_region');

    $resolver = resolve(ConsentRegionResolver::class);

    expect($resolver->resolveFromLocation(['iso_code' => 'US']))
        ->toBe(AnalyticsConsentRegion::OutsideUkOrEurope);
});
