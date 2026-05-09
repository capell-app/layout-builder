<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailTrackingToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTrackingToken>
 */
class EmailTrackingTokenFactory extends Factory
{
    protected $model = EmailTrackingToken::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email_recipient_id' => EmailRecipient::factory(),
            'token_hash' => hash('sha256', $this->faker->uuid()),
            'type' => 'open',
            'destination_url' => null,
            'expires_at' => now()->addDays(180)->toImmutable(),
            'consumed_at' => null,
        ];
    }
}
