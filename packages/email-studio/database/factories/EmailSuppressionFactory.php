<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Database\Factories;

use Capell\EmailStudio\Enums\SuppressionReason;
use Capell\EmailStudio\Models\EmailSuppression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailSuppression>
 */
class EmailSuppressionFactory extends Factory
{
    protected $model = EmailSuppression::class;

    public function definition(): array
    {
        $email = $this->faker->unique()->safeEmail();
        $normalizedEmail = strtolower($email);

        return [
            'site_id' => null,
            'site_scope_key' => 'global',
            'email' => $email,
            'normalized_email' => $normalizedEmail,
            'email_hash' => hash('sha256', $normalizedEmail),
            'reason' => SuppressionReason::Manual,
            'source' => 'manual',
            'notes' => null,
            'suppressed_at' => now()->toImmutable(),
            'released_at' => null,
        ];
    }
}
