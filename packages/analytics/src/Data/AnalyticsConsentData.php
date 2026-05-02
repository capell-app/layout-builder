<?php

declare(strict_types=1);

namespace Capell\Analytics\Data;

use Capell\Analytics\Enums\AnalyticsConsentCategory;
use Spatie\LaravelData\Data;

final class AnalyticsConsentData extends Data
{
    public function __construct(
        public bool $essential = true,
        public bool $analytics = false,
        public bool $marketing = false,
        public bool $preferences = false,
    ) {}

    /**
     * @return list<AnalyticsConsentCategory>
     */
    public function enabledCategories(): array
    {
        $categories = [
            AnalyticsConsentCategory::Essential,
        ];

        if ($this->analytics) {
            $categories[] = AnalyticsConsentCategory::Analytics;
        }

        if ($this->marketing) {
            $categories[] = AnalyticsConsentCategory::Marketing;
        }

        if ($this->preferences) {
            $categories[] = AnalyticsConsentCategory::Preferences;
        }

        return $categories;
    }
}
