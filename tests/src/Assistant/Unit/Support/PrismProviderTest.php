<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;

it('returns an AiResponse from a chat call', function (): void {
    $fake = Prism::fake([
        TextResponseFake::make()->withText('Hello there')->withUsage(new Usage(10, 20)),
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
