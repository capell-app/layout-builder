<?php

declare(strict_types=1);

use Capell\SeoTools\Support\PrismProvider;
use Illuminate\Support\Facades\Cache;

uses()->group('admin-ai');

it('opens circuit after repeated failures', function (): void {
    $provider = resolve(PrismProvider::class);

    Cache::shouldReceive('get')->andReturn(false);

    $caught = 0;
    for ($i = 0; $i < 5; $i++) {
        try {
            $provider->chat(['messages' => []]);
        } catch (Throwable) {
            $caught++;
        }
    }

    expect($caught)->toBe(5);
});
