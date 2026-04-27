<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('no two packages ship a migration file with the same basename', function (): void {
    $migrations = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->path('database/migrations')
        ->name('*.php');

    /** @var array<string, list<string>> $byBasename */
    $byBasename = [];

    foreach ($migrations as $migration) {
        $basename = $migration->getBasename('.php');
        $byBasename[$basename][] = $migration->getRelativePathname();
    }

    $duplicates = array_filter($byBasename, fn (array $paths): bool => count($paths) > 1);

    // Tracked in docs/test-coverage-plan.md §4a — `alter_tags_table.php` is
    // identical in both `blog` and `tags`. When the duplicate is removed
    // (likely from blog, since tags owns the table), drop this expected
    // entry and the test will continue to guard against new collisions.
    $expected = [
        'alter_tags_table' => [
            'blog/database/migrations/alter_tags_table.php',
            'tags/database/migrations/alter_tags_table.php',
        ],
    ];

    foreach ($duplicates as $name => $paths) {
        sort($duplicates[$name]);
    }
    foreach ($expected as $name => $paths) {
        sort($expected[$name]);
    }

    expect($duplicates)->toBe(
        $expected,
        "New migration filename collision detected across packages — Laravel's batch runner uses the basename as the key:" .
        "\n" . json_encode($duplicates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('no two packages ship a settings migration with the same basename', function (): void {
    $migrations = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->path('database/settings')
        ->name('*.php');

    /** @var array<string, list<string>> $byBasename */
    $byBasename = [];

    foreach ($migrations as $migration) {
        $basename = $migration->getBasename('.php');
        $byBasename[$basename][] = $migration->getRelativePathname();
    }

    $duplicates = array_filter($byBasename, fn (array $paths): bool => count($paths) > 1);

    expect($duplicates)->toBe(
        [],
        'Settings migration basenames must be unique — they are tracked by name in the settings.migrations table:' .
        "\n" . json_encode($duplicates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});
