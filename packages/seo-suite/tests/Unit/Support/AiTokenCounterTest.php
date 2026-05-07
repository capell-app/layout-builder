<?php

declare(strict_types=1);

use Capell\SeoSuite\Support\AiTokenCounter;

it('estimates token usage with model-specific multipliers', function (): void {
    $counter = new AiTokenCounter;

    expect($counter->estimate(str_repeat('a', 40), 'gpt-4-turbo'))->toBe(10)
        ->and($counter->estimate(str_repeat('a', 40), 'gpt-3.5-turbo'))->toBe(11)
        ->and($counter->estimate(str_repeat('a', 40), 'gpt-4o'))->toBe(9)
        ->and($counter->estimate(str_repeat('a', 40), 'unknown-model'))->toBe(10);
});

it('normalizes provider token usage payloads', function (): void {
    $counter = new AiTokenCounter;

    expect($counter->count([
        'prompt_tokens' => '12',
        'completion_tokens' => 8,
        'total_tokens' => null,
    ]))->toBe([
        'prompt_tokens' => 12,
        'completion_tokens' => 8,
        'total_tokens' => 0,
    ])
        ->and($counter->countFromString('not an array'))->toBe([
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);
});

it('detects when estimated tokens exceed a configured limit', function (): void {
    $counter = new AiTokenCounter;

    expect($counter->wouldExceedLimit(101, 100))->toBeTrue()
        ->and($counter->wouldExceedLimit(100, 100))->toBeFalse();
});
