<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Filament\Pages\SitemapPage;
use Capell\SeoSuite\Support\Creator\SitemapPageCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
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

    livewire(SitemapPage::class)
        ->assertSuccessful();
});
