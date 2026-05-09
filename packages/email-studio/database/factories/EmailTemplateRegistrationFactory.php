<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Models\EmailTemplateRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplateRegistration>
 */
class EmailTemplateRegistrationFactory extends Factory
{
    protected $model = EmailTemplateRegistration::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'template_key' => 'registered-' . $this->faker->unique()->slug(3),
            'package_name' => 'capell-app/email-studio',
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'variables' => ['name', 'email'],
        ];
    }
}
