<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\EmailMessageStatus;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
{
    protected $model = EmailMessage::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email_profile_id' => EmailProfile::factory(),
            'email_template_id' => EmailTemplate::factory(),
            'email_template_variant_id' => EmailTemplateVariant::factory(),
            'status' => EmailMessageStatus::Queued,
            'subject' => $this->faker->sentence(),
            'preview_text' => $this->faker->sentence(),
            'rendered_html' => '<p>' . e($this->faker->sentence()) . '</p>',
            'rendered_text' => $this->faker->sentence(),
            'context_snapshot' => [],
            'headers' => [],
            'triggered_by_type' => null,
            'triggered_by_id' => null,
            'queued_at' => now()->toImmutable(),
            'sent_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ];
    }
}
