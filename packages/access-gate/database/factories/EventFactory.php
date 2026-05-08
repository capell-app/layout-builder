<?php

declare(strict_types=1);

namespace Capell\AccessGate\Database\Factories;

use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_area_id' => Area::factory(),
            'registration_id' => null,
            'grant_id' => null,
            'claim_token_id' => null,
            'browser_token_id' => null,
            'user_id' => null,
            'type' => EventType::AreaCreated,
            'subject_type' => null,
            'subject_id' => null,
            'payload' => [],
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
