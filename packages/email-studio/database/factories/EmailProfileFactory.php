<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\EmailProviderType;
use Capell\EmailStudio\Models\EmailProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailProfile>
 */
class EmailProfileFactory extends Factory
{
    protected $model = EmailProfile::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'name' => $this->faker->company() . ' SMTP',
            'provider' => EmailProviderType::Smtp,
            'webhook_endpoint_token_hash' => hash('sha256', $this->faker->uuid()),
            'from_email' => $this->faker->safeEmail(),
            'from_name' => $this->faker->company(),
            'reply_to_email' => $this->faker->safeEmail(),
            'reply_to_name' => $this->faker->name(),
            'is_default' => false,
            'track_opens' => true,
            'track_clicks' => true,
            'provider_settings' => [],
        ];
    }
}
