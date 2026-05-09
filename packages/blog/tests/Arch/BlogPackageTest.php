<?php

declare(strict_types=1);
use Symfony\Component\Finder\Finder;

it('declares navigation as an explicit package dependency', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $capellManifestContents = file_get_contents($packagePath . '/capell.json');
    $composerManifestContents = file_get_contents($packagePath . '/composer.json');

    $capellManifest = json_decode(
        $capellManifestContents === false ? '[]' : $capellManifestContents,
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );
    $composerManifest = json_decode(
        $composerManifestContents === false ? '[]' : $composerManifestContents,
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($capellManifest['dependencies']['requires'])->toContain('capell-app/navigation')
        ->and($composerManifest['require'])->toHaveKey('capell-app/navigation');
});

it('declares site discovery as an explicit package dependency', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $capellManifestContents = file_get_contents($packagePath . '/capell.json');
    $composerManifestContents = file_get_contents($packagePath . '/composer.json');

    $capellManifest = json_decode(
        $capellManifestContents === false ? '[]' : $capellManifestContents,
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );
    $composerManifest = json_decode(
        $composerManifestContents === false ? '[]' : $composerManifestContents,
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($capellManifest['dependencies']['requires'])->toContain('capell-app/site-discovery')
        ->and($composerManifest['require'])->toHaveKey('capell-app/site-discovery');
});

it('keeps blog package references inside the blog source package except intentional bridges', function (): void {
    $rootPath = dirname(__DIR__, 4);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($rootPath . '/packages')
        ->path('/\/src\//')
        ->name('*.php')
        ->contains('Capell\\Blog');

    foreach ($files as $file) {
        $relativePath = str_replace($rootPath . '/', '', $file->getPathname());

        if (str_starts_with($relativePath, 'packages/blog/src/')) {
            continue;
        }

        $violations[] = $relativePath;
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\Blog')
    ->classes()
    ->toUseStrictEquality();

arch('blog package does not depend on seo-suite')
    ->expect('Capell\Blog')
    ->not->toUse('Capell\SeoSuite');
