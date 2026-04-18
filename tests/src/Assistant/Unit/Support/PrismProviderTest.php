<?php

declare(strict_types=1);

use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\PrismProvider;
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\Testing\PrismFake;
use EchoLabs\Prism\ValueObjects\Usage;

it('returns an AiResponse from a chat call', function (): void {
    $fake = Prism::fake([
        PrismFake::text('Hello there')->withUsage(new Usage(10, 20)),
    ]);

    $provider = new PrismProvider([
        'provider' => 'openai',
        'model' => 'gpt-4o',
        'max_retries' => 1,
        'retry_delay_ms' => 0,
    ]);

    $response = $provider->chat([
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => 'You are helpful.'],
            ['role' => 'user', 'content' => 'Say hello.'],
        ],
    ]);

    expect($response)->toBeInstanceOf(AiResponse::class)
        ->and($response->content)->toBe('Hello there')
        ->and($response->tokensUsed)->toBe(30);

    $fake->assertCallCount(1);
});
