<?php

declare(strict_types=1);

use Capell\Redirects\Contracts\RedirectRecorder;
use Capell\Redirects\Contracts\RedirectResolver;

it('registers redirects config and default contracts', function (): void {
    expect(config('redirects.auto_redirects.status_code'))->toBe(301)
        ->and(resolve(RedirectResolver::class))->toBeInstanceOf(RedirectResolver::class)
        ->and(resolve(RedirectRecorder::class))->toBeInstanceOf(RedirectRecorder::class);
});
