<?php

declare(strict_types=1);

namespace Capell\Insights\Database\Factories;

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Models\InsightsVisit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InsightsVisit>
 */
class InsightsVisitFactory extends Factory
{
    protected $model = InsightsVisit::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'site_id' => null,
            'language_id' => null,
            'consent_region' => InsightsConsentRegion::Unknown,
            'consent_status' => InsightsConsentStatus::Pending,
            'landing_url' => 'https://example.test/',
            'referrer_url' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'ip_hash' => hash('sha256', '203.0.113.10'),
            'user_agent_hash' => hash('sha256', 'Capell Test Browser'),
            'started_at' => now()->toImmutable(),
            'last_seen_at' => now()->toImmutable(),
        ];
    }
}
