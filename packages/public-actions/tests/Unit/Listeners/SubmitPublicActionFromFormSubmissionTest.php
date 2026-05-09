<?php

declare(strict_types=1);

use Capell\PublicActions\Listeners\SubmitPublicActionFromFormSubmission;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionSubmission;

it('submits form builder event payloads when submissions are not stored', function (): void {
    config()->set('capell-public-actions.form_builder.mappings', [
        'lead-form' => 'lead-capture',
    ]);

    PublicAction::factory()->create([
        'key' => 'lead-capture',
        'handler_key' => 'test.handler',
    ]);

    resolve(SubmitPublicActionFromFormSubmission::class)->handle(new class
    {
        public object $form;

        /** @var array<string, mixed> */
        public array $payload = [
            'email' => 'person@example.test',
            'name' => 'Mona',
        ];

        public function __construct()
        {
            $this->form = new class
            {
                public string $handle = 'lead-form';

                public function getKey(): int
                {
                    return 123;
                }
            };
        }
    });

    $submission = PublicActionSubmission::query()->firstOrFail();

    expect($submission->payload)
        ->toMatchArray([
            'email' => 'person@example.test',
            'name' => 'Mona',
        ])
        ->and($submission->source_type)->toBe('form_builder')
        ->and($submission->source_id)->toBe('123');
});
