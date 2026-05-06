<?php

declare(strict_types=1);

use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

it('casts payload, meta, status, and submitted timestamp', function (): void {
    $submission = Submission::factory()->create([
        'payload' => ['values' => ['email' => 'ben@example.com']],
        'meta' => ['ip_address' => '127.0.0.1'],
        'status' => 'read',
        'submitted_at' => now(),
    ]);

    $submission->refresh();

    expect($submission->payload)->toBeInstanceOf(SubmissionPayloadData::class)
        ->and($submission->payload->values)->toBe(['email' => 'ben@example.com'])
        ->and($submission->meta)->toBeInstanceOf(SubmissionMetaData::class)
        ->and($submission->meta->ipAddress)->toBe('127.0.0.1')
        ->and($submission->status)->toBe(SubmissionStatus::Read)
        ->and($submission->submitted_at)->toBeInstanceOf(CarbonInterface::class);
});

it('belongs to a form', function (): void {
    $form = Form::factory()->create();
    $submission = Submission::factory()->for($form)->create();

    expect($submission->form->is($form))->toBeTrue();
});

it('stores the same site as its form', function (): void {
    $form = Form::factory()->create();
    $submission = Submission::factory()->for($form)->create();

    expect($submission->site_id)->toBe($form->site_id)
        ->and($submission->site->is($form->site))->toBeTrue();
});

it('encrypts payload and meta at rest while restoring structured data', function (): void {
    $submission = Submission::factory()->create([
        'payload' => ['values' => ['email' => 'ben@example.com']],
        'meta' => ['ip_address' => '127.0.0.1'],
    ]);

    $rawSubmission = DB::table('submissions')
        ->where('id', $submission->getKey())
        ->first();

    expect($rawSubmission->payload)->not->toContain('ben@example.com')
        ->and($rawSubmission->meta)->not->toContain('127.0.0.1');

    $submission->refresh();

    expect($submission->payload)->toBeInstanceOf(SubmissionPayloadData::class)
        ->and($submission->payload->values)->toBe(['email' => 'ben@example.com'])
        ->and($submission->meta)->toBeInstanceOf(SubmissionMetaData::class)
        ->and($submission->meta->ipAddress)->toBe('127.0.0.1');
});

it('reads legacy plaintext JSON submission payloads', function (): void {
    $submission = Submission::factory()->create();

    DB::table('submissions')
        ->where('id', $submission->getKey())
        ->update([
            'payload' => json_encode(['values' => ['email' => 'legacy@example.com']], JSON_THROW_ON_ERROR),
            'meta' => json_encode(['ip_address' => '10.0.0.1'], JSON_THROW_ON_ERROR),
        ]);

    $submission->refresh();

    expect($submission->payload->values)->toBe(['email' => 'legacy@example.com'])
        ->and($submission->meta->ipAddress)->toBe('10.0.0.1');
});
