<?php

declare(strict_types=1);

namespace Capell\Insights\Database\Factories;

use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsightsEvent>
 */
class InsightsEventFactory extends Factory
{
    protected $model = InsightsEvent::class;

    public function definition(): array
    {
        return [
            'visit_id' => InsightsVisit::factory(),
            'site_id' => null,
            'language_id' => null,
            'type' => InsightsEventType::PageView,
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
