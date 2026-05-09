<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\EmailTemplateStatus;
use Capell\EmailStudio\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'key' => 'template-' . $this->faker->unique()->slug(3),
            'name' => $this->faker->sentence(3),
            'status' => EmailTemplateStatus::Approved,
            'description' => $this->faker->sentence(),
            'variables' => ['name', 'email'],
        ];
    }
}
