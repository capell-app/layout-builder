<?php

declare(strict_types=1);

namespace Capell\Analytics\Database\Factories;

use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalyticsVisit>
 */
class AnalyticsVisitFactory extends Factory
{
    protected $model = AnalyticsVisit::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'site_id' => null,
            'language_id' => null,
            'consent_region' => AnalyticsConsentRegion::Unknown,
            'consent_status' => AnalyticsConsentStatus::Pending,
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
