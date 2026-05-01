<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

it('mirrors theme manifest requirements in composer requirements', function (string $themePath): void {
    $packagePath = dirname(__DIR__, 2) . '/' . $themePath;
    $manifest = json_decode((string) file_get_contents($packagePath . '/capell.json'), true);
    $composer = json_decode((string) file_get_contents($packagePath . '/composer.json'), true);

    expect($manifest)->toBeArray();
    expect($composer)->toBeArray();

    $manifestRequirements = Arr::wrap($manifest['requires'] ?? []);

    if (is_string($manifest['extends'] ?? null)) {
        $manifestRequirements[] = $manifest['extends'];
    }

    expect(array_keys($composer['require'] ?? []))
        ->toContain(...array_values(array_unique($manifestRequirements)));
})->with([
    'agency theme' => 'agency',
    'corporate theme' => 'corporate',
    'saas theme' => 'saas',
]);
