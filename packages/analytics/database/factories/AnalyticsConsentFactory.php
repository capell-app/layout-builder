<?php

declare(strict_types=1);

namespace Capell\Analytics\Database\Factories;

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsConsent>
 */
class AnalyticsConsentFactory extends Factory
{
    protected $model = AnalyticsConsent::class;

    public function definition(): array
    {
        return [
            'visit_id' => AnalyticsVisit::factory(),
            'consent_region' => AnalyticsConsentRegion::UkOrEurope,
            'status' => AnalyticsConsentStatus::Granular,
            'categories' => [
                'essential' => true,
                'analytics' => true,
                'marketing' => false,
                'preferences' => false,
            ],
            'policy_version' => '1.0',
            'terms_accepted_at' => null,
            'decided_at' => now()->toImmutable(),
            'ip_hash' => hash('sha256', '203.0.113.10'),
            'user_agent_hash' => hash('sha256', 'Capell Test Browser'),
        ];
    }
}
