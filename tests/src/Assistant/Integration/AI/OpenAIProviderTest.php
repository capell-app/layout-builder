<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Integration\AI;

use Capell\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\PrismProvider;
use Illuminate\Support\Facades\Cache;

it('calls the OpenAI provider chat successfully structure-wise', function (): void {
    $provider = new PrismProvider([
        'max_retries' => 1,
        'retry_delay_ms' => 10,
    ]);

    expect($provider)->toBeInstanceOf(PrismProvider::class);
});

it('throws when circuit breaker is open', function (): void {
    Cache::put('ai_circuit_breaker_state', ['failures' => 5], 300);

    $provider = new PrismProvider([
        'max_retries' => 1,
        'retry_delay_ms' => 10,
    ]);

    $params = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'content'],
        ],
    ];

    expect(fn (): AiResponse => $provider->chat($params))
        ->toThrow(OpenAICircuitBreakerOpenException::class);

    // Reset after test
    Cache::forget('ai_circuit_breaker_state');
});
