<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\EmailRecipientStatus;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailRecipient>
 */
class EmailRecipientFactory extends Factory
{
    protected $model = EmailRecipient::class;

    public function definition(): array
    {
        $email = $this->faker->safeEmail();
        $normalizedEmail = strtolower($email);

        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email_message_id' => EmailMessage::factory(),
            'type' => 'to',
            'email' => $email,
            'normalized_email' => $normalizedEmail,
            'email_hash' => hash('sha256', $normalizedEmail),
            'name' => $this->faker->name(),
            'status' => EmailRecipientStatus::Queued,
            'provider_message_id' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'opened_at' => null,
            'clicked_at' => null,
            'bounced_at' => null,
            'complained_at' => null,
            'replied_at' => null,
            'suppressed_at' => null,
            'failure_reason' => null,
        ];
    }
}
