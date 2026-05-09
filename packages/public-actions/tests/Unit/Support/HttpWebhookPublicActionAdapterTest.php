<?php

declare(strict_types=1);

use Capell\PublicActions\Actions\DispatchPublicActionDestinationAction;
use Capell\PublicActions\Enums\PublicActionDispatchStatus;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionDestination;
use Capell\PublicActions\Models\PublicActionDispatchAttempt;
use Capell\PublicActions\Models\PublicActionSubmission;
use Capell\PublicActions\Support\Providers\HttpWebhookPublicActionAdapter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('dispatches a submission to a json webhook and records a successful attempt', function (): void {
    Http::fake([
        'https://hooks.example.test/success' => Http::response('{"id":"accepted"}', 202, ['X-Request-Id' => 'provider-123']),
    ]);

    $action = PublicAction::factory()->create([
        'key' => 'preview-access',
        'name' => 'Preview access',
    ]);
    $destination = PublicActionDestination::factory()->for($action, 'action')->create([
        'adapter' => 'http_webhook',
        'endpoint_url' => 'https://hooks.example.test/success',
        'secret' => 'signing-secret',
        'headers' => ['X-Custom' => 'custom-value'],
        'settings' => ['method' => 'POST', 'timeout_seconds' => 5],
    ]);
    $submission = PublicActionSubmission::factory()->for($action, 'action')->create([
        'payload' => ['email' => 'person@example.test'],
    ]);

    $result = resolve(DispatchPublicActionDestinationAction::class)->handle($destination, $submission);

    expect($result->success)->toBeTrue()
        ->and($result->responseStatus)->toBe(202)
        ->and($result->externalId)->toBe('provider-123');

    $attempt = PublicActionDispatchAttempt::query()->firstOrFail();

    expect($attempt->status)->toBe(PublicActionDispatchStatus::Succeeded)
        ->and($attempt->attempt)->toBe(1)
        ->and($attempt->response_status)->toBe(202)
        ->and($attempt->request_hash)->toHaveLength(64);

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://hooks.example.test/success'
            && $request->hasHeader('X-Custom', 'custom-value')
            && $request->hasHeader('X-Capell-Signature')
            && data_get($data, 'action.key') === 'preview-access'
            && data_get($data, 'payload.email') === 'person@example.test';
    });
});

it('records provider failures as retryable and redacts response summaries', function (): void {
    Http::fake([
        'https://hooks.example.test/fail' => Http::response('failed for secret-token at https://hooks.example.test/fail', 500),
    ]);

    $destination = PublicActionDestination::factory()->create([
        'adapter' => 'http_webhook',
        'endpoint_url' => 'https://hooks.example.test/fail',
        'secret' => 'secret-token',
        'headers' => ['Authorization' => 'Bearer secret-token'],
    ]);
    $submission = PublicActionSubmission::factory()->create();

    $result = resolve(DispatchPublicActionDestinationAction::class)->handle($destination, $submission);
    $attempt = PublicActionDispatchAttempt::query()->firstOrFail();

    expect($result->success)->toBeFalse()
        ->and($attempt->status)->toBe(PublicActionDispatchStatus::Retryable)
        ->and($attempt->response_status)->toBe(500)
        ->and($attempt->response_summary)->toContain('[redacted]')
        ->and($attempt->response_summary)->not->toContain('secret-token')
        ->and($attempt->response_summary)->not->toContain('https://hooks.example.test/fail');
});

it('records connection failures as retryable without leaking secrets', function (): void {
    Http::fake(function (): never {
        throw new ConnectionException('Could not connect with secret-token');
    });

    $destination = PublicActionDestination::factory()->create([
        'adapter' => 'http_webhook',
        'endpoint_url' => 'https://hooks.example.test/timeout',
        'secret' => 'secret-token',
    ]);
    $submission = PublicActionSubmission::factory()->create();

    $result = resolve(DispatchPublicActionDestinationAction::class)->handle($destination, $submission);
    $attempt = PublicActionDispatchAttempt::query()->firstOrFail();

    expect($result->success)->toBeFalse()
        ->and($attempt->status)->toBe(PublicActionDispatchStatus::Retryable)
        ->and($attempt->error_message)->toContain('[redacted]')
        ->and($attempt->error_message)->not->toContain('secret-token');
});

it('increments retry attempts while keeping the same request hash for unchanged payloads', function (): void {
    Http::fake([
        'https://hooks.example.test/retry' => Http::response('try again', 503),
    ]);

    $destination = PublicActionDestination::factory()->create([
        'adapter' => 'http_webhook',
        'endpoint_url' => 'https://hooks.example.test/retry',
    ]);
    $submission = PublicActionSubmission::factory()->create([
        'payload' => ['email' => 'same@example.test'],
    ]);

    resolve(DispatchPublicActionDestinationAction::class)->handle($destination, $submission);
    resolve(DispatchPublicActionDestinationAction::class)->handle($destination, $submission);

    $attempts = PublicActionDispatchAttempt::query()->orderBy('attempt')->get();

    expect($attempts)->toHaveCount(2)
        ->and($attempts[0]->attempt)->toBe(1)
        ->and($attempts[1]->attempt)->toBe(2)
        ->and($attempts[0]->request_hash)->toBe($attempts[1]->request_hash);
});

it('can dispatch with the adapter directly for registered adapter use cases', function (): void {
    Http::fake([
        'https://hooks.example.test/direct' => Http::response('', 204),
    ]);

    $destination = PublicActionDestination::factory()->create([
        'adapter' => 'http_webhook',
        'endpoint_url' => 'https://hooks.example.test/direct',
        'settings' => ['method' => 'PUT'],
    ]);
    $submission = PublicActionSubmission::factory()->create();

    $result = resolve(HttpWebhookPublicActionAdapter::class)->dispatch($destination, $submission);

    expect($result->success)->toBeTrue()
        ->and(PublicActionDispatchAttempt::query()->firstOrFail()->status)->toBe(PublicActionDispatchStatus::Succeeded);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT');
});

it('blocks private webhook endpoint hosts', function (): void {
    Http::fake();

    $destination = PublicActionDestination::factory()->create([
        'adapter' => 'http_webhook',
        'endpoint_url' => 'https://127.0.0.1/metadata',
    ]);
    $submission = PublicActionSubmission::factory()->create();

    $result = resolve(HttpWebhookPublicActionAdapter::class)->dispatch($destination, $submission);

    expect($result->success)->toBeFalse()
        ->and(PublicActionDispatchAttempt::query()->firstOrFail()->status)->toBe(PublicActionDispatchStatus::Failed);

    Http::assertNothingSent();
});
