<?php

declare(strict_types=1);

namespace Capell\Insights\Support\Consent;

use Capell\Insights\Enums\InsightsConsentRegion;
use Throwable;

final class ConsentRegionResolver
{
    private const UK_AND_EUROPE_COUNTRY_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
        'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT',
        'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'UK', 'IS', 'LI', 'NO', 'CH',
    ];

    public function resolve(): InsightsConsentRegion
    {
        $configuredRegion = $this->resolveConfiguredRegion();

        if ($configuredRegion instanceof InsightsConsentRegion) {
            return $configuredRegion;
        }

        if (! function_exists('geoip')) {
            return InsightsConsentRegion::Unknown;
        }

        try {
            return $this->resolveFromLocation(geoip()->getLocation());
        } catch (Throwable) {
            return InsightsConsentRegion::Unknown;
        }
    }

    public function resolveFromLocation(mixed $location): InsightsConsentRegion
    {
        $countryCode = $this->countryCodeFromLocation($location);

        if ($countryCode === null) {
            return InsightsConsentRegion::Unknown;
        }

        if (in_array($countryCode, self::UK_AND_EUROPE_COUNTRY_CODES, true)) {
            return InsightsConsentRegion::UkOrEurope;
        }

        return InsightsConsentRegion::OutsideUkOrEurope;
    }

    private function resolveConfiguredRegion(): ?InsightsConsentRegion
    {
        $configuredRegion = config('capell-insights.default_consent_region');

        if (! is_string($configuredRegion)) {
            return null;
        }

        return InsightsConsentRegion::tryFrom($configuredRegion);
    }

    private function countryCodeFromLocation(mixed $location): ?string
    {
        $countryCode = null;

        if (is_array($location)) {
            $countryCode = $location['iso_code']
                ?? $location['isoCode']
                ?? $location['country_code']
                ?? $location['countryCode']
                ?? null;
        }

        if (is_object($location)) {
            $countryCode = $location->iso_code
                ?? $location->isoCode
                ?? $location->country_code
                ?? $location->countryCode
                ?? null;
        }

        if (! is_string($countryCode) || trim($countryCode) === '') {
            return null;
        }

        return strtoupper(trim($countryCode));
    }
}
