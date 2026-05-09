<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\EmailVariantStatus;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplateVariant>
 */
class EmailTemplateVariantFactory extends Factory
{
    protected $model = EmailTemplateVariant::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email_template_id' => EmailTemplate::factory(),
            'email_profile_id' => null,
            'locale' => 'en',
            'status' => EmailVariantStatus::Active,
            'version' => 1,
            'subject' => 'Hello {{ name }}',
            'preview_text' => 'A short preview',
            'html_body' => '<p>Hello {{ name }}</p>',
            'text_body' => 'Hello {{ name }}',
            'approved_at' => now()->toImmutable(),
            'approved_by' => null,
        ];
    }
}
