<?php

declare(strict_types=1);

use Livewire\Blaze\Blaze;

it('registers installed package component directories with Blaze', function (string $file): void {
    expect(file_exists($file))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($file))->toBeTrue();
})->with([
    'blog' => fn (): string => dirname(__DIR__, 3) . '/packages/blog/resources/views/components/tag.blade.php',
    'layout-builder' => fn (): string => dirname(__DIR__, 3) . '/packages/layout-builder/resources/views/components/widget/default.blade.php',
    'seo-suite' => fn (): string => dirname(__DIR__, 3) . '/packages/seo-suite/resources/views/components/schema/graph.blade.php',
    'foundation-theme-package' => fn (): string => dirname(__DIR__, 3) . '/packages/foundation-theme/resources/views/components/button/index.blade.php',
]);

it('does not register direct-rendered package views with Blaze', function (string $file): void {
    expect(file_exists($file))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($file))->toBeFalse();
})->with([
    'navigation-form-partial' => fn (): string => dirname(__DIR__, 3) . '/packages/navigation/resources/views/components/page/navigations.blade.php',
    'frontend-authoring-beacon-script' => fn (): string => dirname(__DIR__, 3) . '/packages/frontend-authoring/resources/views/authoring/bootstrap-script.blade.php',
    'seo-suite-sitemap-page' => fn (): string => dirname(__DIR__, 3) . '/packages/seo-suite/resources/views/components/pages/sitemap.blade.php',
    'publishing-studio-livewire-view' => fn (): string => dirname(__DIR__, 3) . '/packages/publishing-studio/resources/views/components/publishing-studio/diff-panel.blade.php',
]);
