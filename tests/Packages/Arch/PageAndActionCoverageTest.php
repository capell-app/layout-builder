<?php

declare(strict_types=1);

use Filament\Actions\Action as FilamentAction;
use Filament\Pages\Page as FilamentPage;
use Filament\Resources\Pages\Page as FilamentResourcePage;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsObject;
use Symfony\Component\Finder\Finder;

it('keeps every source action autoloadable with a public action entrypoint', function (): void {
    $actions = packageSourceClassesMatching('#/src/.*/Actions/.*\.php$#');

    expect($actions)->not->toBeEmpty();

    $invalidActions = [];

    foreach ($actions as $class => $path) {
        if (! class_exists($class)) {
            $invalidActions[$path] = 'Class is not autoloadable.';

            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            continue;
        }

        $hasActionEntrypoint = $reflection->hasMethod('handle')
            && $reflection->getMethod('handle')->isPublic();

        $usesLaravelActionTrait = in_array(AsObject::class, $reflection->getTraitNames(), true)
            || in_array(AsAction::class, $reflection->getTraitNames(), true);

        $isFilamentAction = $reflection->isSubclassOf(FilamentAction::class);

        if (! $hasActionEntrypoint && ! $usesLaravelActionTrait && ! $isFilamentAction) {
            $invalidActions[$path] = 'Concrete action has no public handle(), AsObject/AsAction trait, or Filament action base class.';
        }
    }

    expect($invalidActions)->toBe(
        [],
        'Every source action should expose a public action entrypoint:' . PHP_EOL .
        json_encode($invalidActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('keeps every Filament page autoloadable under a real Filament page base class', function (): void {
    $pages = packageSourceClassesMatching('#/src/Filament/(?:Pages/[^/]+|Resources/(?:[^/]+/Pages|Pages/Pages)/[^/]+)\.php$#');

    expect($pages)->not->toBeEmpty();

    $invalidPages = [];

    foreach ($pages as $class => $path) {
        if (! class_exists($class)) {
            $invalidPages[$path] = 'Class is not autoloadable.';

            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            continue;
        }

        if (! $reflection->isSubclassOf(FilamentPage::class) && ! $reflection->isSubclassOf(FilamentResourcePage::class)) {
            $invalidPages[$path] = 'Page class does not extend a Filament page base class.';
        }
    }

    expect($invalidPages)->toBe(
        [],
        'Every source page should be loadable as a Filament page:' . PHP_EOL .
        json_encode($invalidPages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

/**
 * @return array<class-string, string>
 */
function packageSourceClassesMatching(string $pathPattern): array
{
    $files = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->name('*.php');

    $classes = [];

    foreach ($files as $file) {
        if (! preg_match($pathPattern, '/' . $file->getRelativePathname())) {
            continue;
        }

        $contents = $file->getContents();

        if (! preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatch)) {
            continue;
        }

        if (! preg_match('/(?:^|\s)(?:abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatch)) {
            continue;
        }

        $classes[$namespaceMatch[1] . '\\' . $classMatch[1]] = $file->getRelativePathname();
    }

    ksort($classes);

    return $classes;
}
