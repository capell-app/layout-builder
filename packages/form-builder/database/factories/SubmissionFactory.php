<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Database\Factories;

use Capell\Core\Models\Site;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Submission $submission): void {
            if ($submission->site_id !== null) {
                return;
            }

            if ($submission->relationLoaded('form') && $submission->form !== null) {
                $submission->site_id = $submission->form->site_id;

                return;
            }

            if ($submission->form_id !== null) {
                $submission->site_id = Form::query()->findOrFail($submission->form_id)->site_id;
            }
        });
    }

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'site_id' => fn (array $attributes): int => Form::query()->findOrFail($attributes['form_id'])->site_id,
            'payload' => [
                'values' => [
                    'email' => $this->faker->safeEmail(),
                ],
            ],
            'meta' => [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'FormBuilder test agent',
                'url' => 'https://example.test/contact',
                'referer' => null,
            ],
            'status' => SubmissionStatus::New,
            'submitted_at' => now(),
        ];
    }

    public function site(Site $site): static
    {
        return $this->for(Form::factory()->for($site));
    }
}
