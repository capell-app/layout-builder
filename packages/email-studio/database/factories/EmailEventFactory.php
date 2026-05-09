<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\EmailEventType;
use Capell\EmailStudio\Models\EmailEvent;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailEvent>
 */
class EmailEventFactory extends Factory
{
    protected $model = EmailEvent::class;

    public function definition(): array
    {
        $providerEventId = $this->faker->uuid();

        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email_profile_id' => EmailProfile::factory(),
            'email_message_id' => EmailMessage::factory(),
            'email_recipient_id' => EmailRecipient::factory(),
            'type' => EmailEventType::Sent,
            'provider_event_id' => $providerEventId,
            'idempotency_key' => hash('sha256', $providerEventId),
            'provider_payload' => [],
            'occurred_at' => now()->toImmutable(),
        ];
    }
}
