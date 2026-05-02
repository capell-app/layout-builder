<?php

declare(strict_types=1);

use Capell\Address\Support\Language\FlagsService;
use Illuminate\Support\Facades\Cache;

require_once __DIR__ . '/../../../../src/Support/Language/FlagsService.php';

beforeEach(function (): void {
    Cache::flush();
});

it('loads available flag codes from blade country flags', function (): void {
    $service = new FlagsService;

    expect($service->availableFlags())
        ->toContain('gb')
        ->toContain('fr');
});

it('checks whether a flag exists', function (): void {
    $service = new FlagsService;

    expect($service->flagExists('gb'))->toBeTrue()
        ->and($service->flagExists('missing-flag'))->toBeFalse();
});
