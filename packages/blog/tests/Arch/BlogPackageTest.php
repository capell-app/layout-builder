<?php

declare(strict_types=1);
use Capell\Blog\Providers\FrontendServiceProvider;
use Capell\Blog\Support\Sitemap\ArchivesSitemap;
use Capell\Blog\Support\Sitemap\ArticlesSitemap;
use Capell\Blog\Support\Sitemap\TagsSitemap;
use Symfony\Component\Finder\Finder;

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

arch('blog package does not depend on seo-tools outside of sitemap bridges')
    ->expect('Capell\Blog')
    ->not->toUse('Capell\SeoTools')
    ->ignoring([
        ArchivesSitemap::class,
        ArticlesSitemap::class,
        TagsSitemap::class,
        FrontendServiceProvider::class,
    ]);
