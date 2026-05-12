<?php

declare(strict_types=1);

use Capell\Admin\Filament\Pages\SitemapPage;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Site;
use Capell\SiteDiscovery\Support\Creator\SitemapPageCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\View\FileViewFinder;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
    config()->set('view.paths', array_values(array_unique([
        base_path('packages/site-discovery/resources/views'),
        ...config('view.paths', []),
    ])));
    $viewFinder = view()->getFinder();

    if ($viewFinder instanceof FileViewFinder) {
        $viewFinder->prependLocation(base_path('packages/site-discovery/resources/views'));
    }

    view()->addNamespace('capell', base_path('packages/site-discovery/resources/views'));
    view()->addNamespace('capell-site-discovery', base_path('packages/site-discovery/resources/views'));
    view()->getFinder()->flush();
    test()->actingAsAdmin();
});

test('can render page', function (): void {
    Permission::create(['name' => 'View:SitemapPage', 'guard_name' => 'web']);
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:SitemapPage');

    $site = Site::factory()->withTranslations()->create();

    $pageCreator = resolve(SitemapPageCreator::class);

    $pageCreator->createSitemapPage($site);

    $blogCreator = resolve(BlogCreator::class);
    $blogPage = $blogCreator->createBlogPage($site);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage);
    $blogCreator->createTagPage($site, $tagsPage);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $blogCreator->createArchivePage($archivesPage);

    Article::factory()->count(5)->site($site)->withTranslations()->create();

    $page = new SitemapPage;
    $page->mount();

    expect($page->getView())->toBe('capell-admin::filament.pages.sitemap')
        ->and($page->getSitemap())->not->toBeNull();
});
