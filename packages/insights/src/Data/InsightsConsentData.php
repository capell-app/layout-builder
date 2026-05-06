<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Capell\Insights\Enums\InsightsConsentCategory;
use Spatie\LaravelData\Data;

final class InsightsConsentData extends Data
{
    public function __construct(
        public bool $essential = true,
        public bool $insights = false,
        public bool $marketing = false,
        public bool $preferences = false,
    ) {}

    /**
     * @return list<InsightsConsentCategory>
     */
    public function enabledCategories(): array
    {
        $categories = [
            InsightsConsentCategory::Essential,
        ];

        if ($this->insights) {
            $categories[] = InsightsConsentCategory::Insights;
        }

        if ($this->marketing) {
            $categories[] = InsightsConsentCategory::Marketing;
        }

        if ($this->preferences) {
            $categories[] = InsightsConsentCategory::Preferences;
        }

        return $categories;
    }
}
