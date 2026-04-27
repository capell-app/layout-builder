<?php

declare(strict_types=1);

use Capell\Admin\Contracts\TypeSchemaInterface;
use Symfony\Component\Finder\Finder;

/**
 * Filament schemas across packages register into a per-type, per-key map. If
 * two packages ship a schema for the same type that returns the same getKey()
 * value, the second one silently overwrites the first at boot. This test
 * scans every TypeSchemaInterface implementation in the repo and asserts
 * (key, type-folder) is unique.
 */
it('no two TypeSchemaInterface implementations share the same key for the same type', function (): void {
    $files = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->path('src/Filament/Schemas')
        ->name('*.php');

    /** @var array<string, list<string>> $byKey */
    $byKey = [];

    foreach ($files as $file) {
        $relative = $file->getRelativePathname();

        $contents = $file->getContents();

        if (! preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatch)) {
            continue;
        }
        if (! preg_match('/(?:^|\s)class\s+(\w+)/m', $contents, $classMatch)) {
            continue;
        }

        $class = $namespaceMatch[1] . '\\' . $classMatch[1];

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || ! $reflection->implementsInterface(TypeSchemaInterface::class)) {
            continue;
        }

        // Use the schema's own folder (the subpath under src/Filament/Schemas
        // up to but excluding the file) as the collision bucket. This matches
        // how packages organize schemas — siblings in the same folder map to
        // the same registered type, while nested folders (e.g. Layouts/Widgets)
        // are distinct types.
        $type = (string) str($file->getRelativePath())
            ->after('src/Filament/Schemas')
            ->ltrim(DIRECTORY_SEPARATOR);

        if ($type === '') {
            $type = '__root__';
        }

        $key = $class::getKey();
        $byKey[$type . '::' . $key][] = $relative;
    }

    $duplicates = array_filter($byKey, fn (array $paths): bool => count($paths) > 1);

    expect($duplicates)->toBe(
        [],
        'Two or more schemas return the same getKey() for the same schema type — ' .
        "boot order would silently overwrite one of them:\n" .
        json_encode($duplicates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});
