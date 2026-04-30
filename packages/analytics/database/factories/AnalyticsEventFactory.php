<?php

declare(strict_types=1);

namespace Capell\Analytics\Database\Factories;

use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        return [
            'visit_id' => AnalyticsVisit::factory(),
            'site_id' => null,
            'language_id' => null,
            'type' => AnalyticsEventType::PageView,
            'url' => 'https://example.test/',
            'path' => '/',
            'title' => 'Example',
            'occurred_at' => now()->toImmutable(),
            'sequence' => 1,
            'event_name' => null,
            'label' => null,
            'location' => null,
            'target_selector' => null,
            'viewport_x' => null,
            'viewport_y' => null,
            'document_x' => null,
            'document_y' => null,
            'metadata' => [],
        ];
    }
}
