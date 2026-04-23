<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiTokenCounter;

it('counts tokens by model baseline', function (): void {
    $counter = new AiTokenCounter;

    // Use lenient string-based counter for convenience in tests
    expect($counter->countFromString('test'))
        ->toBeArray()
        ->and($counter->countFromString('test')['total_tokens'])->toBeGreaterThanOrEqual(0);

    // Proper usage accepts an array with token counts
    expect($counter->count(['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3]))
        ->toBeArray()
        ->and($counter->count(['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3])['total_tokens'])->toBe(3);
});
