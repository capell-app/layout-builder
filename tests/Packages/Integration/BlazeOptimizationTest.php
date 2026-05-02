<?php

declare(strict_types=1);

use Livewire\Blaze\Blaze;

it('registers installed package component directories with Blaze', function (string $file): void {
    expect(file_exists($file))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($file))->toBeTrue();
})->with([
    'blog' => fn (): string => dirname(__DIR__, 3) . '/packages/blog/resources/views/components/tag.blade.php',
    'mosaic' => fn (): string => dirname(__DIR__, 3) . '/packages/mosaic/resources/views/components/widget/default.blade.php',
    'seo-tools' => fn (): string => dirname(__DIR__, 3) . '/packages/seo-tools/resources/views/components/schema/graph.blade.php',
    'default-theme-package' => fn (): string => dirname(__DIR__, 3) . '/packages/default-theme/resources/views/components/button/index.blade.php',
]);

it('does not register direct-rendered package views with Blaze', function (string $file): void {
    expect(file_exists($file))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($file))->toBeFalse();
})->with([
    'navigation-form-partial' => fn (): string => dirname(__DIR__, 3) . '/packages/navigation/resources/views/components/page/navigations.blade.php',
    'toolbar-controller-snippet' => fn (): string => dirname(__DIR__, 3) . '/packages/toolbar/resources/views/components/toolbar.blade.php',
    'seo-tools-sitemap-page' => fn (): string => dirname(__DIR__, 3) . '/packages/seo-tools/resources/views/components/pages/sitemap.blade.php',
    'workspaces-livewire-view' => fn (): string => dirname(__DIR__, 3) . '/packages/workspaces/resources/views/components/workspaces/diff-panel.blade.php',
]);
