<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailReply>
 */
class EmailReplyFactory extends Factory
{
    protected $model = EmailReply::class;

    public function definition(): array
    {
        $email = $this->faker->safeEmail();
        $normalizedEmail = strtolower($email);

        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email_message_id' => EmailMessage::factory(),
            'email_recipient_id' => EmailRecipient::factory(),
            'provider_message_id' => $this->faker->uuid(),
            'from_email' => $email,
            'normalized_from_email' => $normalizedEmail,
            'from_email_hash' => hash('sha256', $normalizedEmail),
            'from_name' => $this->faker->name(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'provider_payload' => [],
            'received_at' => now()->toImmutable(),
        ];
    }
}
