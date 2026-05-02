<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('every provider class declared in a capell.json manifest is autoloadable', function (): void {
    $manifests = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->name('capell.json')
        ->depth('< 4');

    expect($manifests)->not->toBeEmpty();

    /** @var array<string, string> $missing */
    $missing = [];

    foreach ($manifests as $manifest) {
        $relative = $manifest->getRelativePathname();
        $payload = json_decode($manifest->getContents(), true, flags: JSON_THROW_ON_ERROR);

        $providers = $payload['providers'] ?? [];
        if (! is_array($providers)) {
            continue;
        }

        foreach ($providers as $context => $classes) {
            if (! is_array($classes)) {
                continue;
            }

            foreach ($classes as $class) {
                if (! class_exists($class)) {
                    $missing[$relative . ' [' . $context . ']'] = $class;
                }
            }
        }
    }

    expect($missing)->toBe(
        [],
        'capell.json declares providers that cannot be autoloaded — ' .
        "the manifest and the codebase have drifted apart:\n" .
        json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('no two packages declare the same name in their capell.json manifest', function (): void {
    $manifests = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->name('capell.json')
        ->depth('< 4');

    /** @var array<string, list<string>> $byName */
    $byName = [];

    foreach ($manifests as $manifest) {
        $payload = json_decode($manifest->getContents(), true, flags: JSON_THROW_ON_ERROR);
        $name = $payload['name'] ?? null;

        if (! is_string($name)) {
            continue;
        }

        $byName[$name][] = $manifest->getRelativePathname();
    }

    $duplicates = array_filter($byName, fn (array $paths): bool => count($paths) > 1);

    // Tracked in docs/test-coverage-plan.md §4a — `packages/default-theme/`
    // and `packages/theme-default/` both publish under this name. When the
    // duplicate is resolved, remove this expected entry and the test will
    // continue to enforce uniqueness.
    $expected = [
        'capell-app/default-theme' => [
            'default-theme/capell.json',
            'theme-default/capell.json',
        ],
    ];

    foreach (array_keys($duplicates) as $name) {
        sort($duplicates[$name]);
    }

    foreach (array_keys($expected) as $name) {
        sort($expected[$name]);
    }

    expect($duplicates)->toBe(
        $expected,
        'New duplicate package name detected in capell.json — install order would be undefined:' .
        "\n" . json_encode($duplicates, JSON_PRETTY_PRINT),
    );
});
