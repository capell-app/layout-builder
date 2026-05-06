<?php

declare(strict_types=1);

namespace Capell\Insights\Database\Factories;

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsightsConsent>
 */
class InsightsConsentFactory extends Factory
{
    protected $model = InsightsConsent::class;

    public function definition(): array
    {
        return [
            'visit_id' => InsightsVisit::factory(),
            'consent_region' => InsightsConsentRegion::UkOrEurope,
            'status' => InsightsConsentStatus::Granular,
            'categories' => [
                'essential' => true,
                'insights' => true,
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
