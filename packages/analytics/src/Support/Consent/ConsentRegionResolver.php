<?php

declare(strict_types=1);

namespace Capell\Analytics\Support\Consent;

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Throwable;

final class ConsentRegionResolver
{
    private const UK_AND_EUROPE_COUNTRY_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
        'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT',
        'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'UK', 'IS', 'LI', 'NO', 'CH',
    ];

    public function resolve(): AnalyticsConsentRegion
    {
        $configuredRegion = $this->resolveConfiguredRegion();

        if ($configuredRegion instanceof AnalyticsConsentRegion) {
            return $configuredRegion;
        }

        if (! function_exists('geoip')) {
            return AnalyticsConsentRegion::Unknown;
        }

        try {
            return $this->resolveFromLocation(geoip()->getLocation());
        } catch (Throwable) {
            return AnalyticsConsentRegion::Unknown;
        }
    }

    public function resolveFromLocation(mixed $location): AnalyticsConsentRegion
    {
        $countryCode = $this->countryCodeFromLocation($location);

        if ($countryCode === null) {
            return AnalyticsConsentRegion::Unknown;
        }

        if (in_array($countryCode, self::UK_AND_EUROPE_COUNTRY_CODES, true)) {
            return AnalyticsConsentRegion::UkOrEurope;
        }

        return AnalyticsConsentRegion::OutsideUkOrEurope;
    }

    private function resolveConfiguredRegion(): ?AnalyticsConsentRegion
    {
        $configuredRegion = config('capell-analytics.default_consent_region');

        if (! is_string($configuredRegion)) {
            return null;
        }

        return AnalyticsConsentRegion::tryFrom($configuredRegion);
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
