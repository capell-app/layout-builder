<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\SitemapPage;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can render page', function (): void {
    $site = Site::factory()->create();

    $blogCreator = resolve(BlogCreator::class);
    $blogPage = $blogCreator->createBlogPage($site);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage);
    $blogCreator->createTagPage($site, $tagsPage);

    Page::factory()->site($site)->withTranslations($site->languages)->count(5)->create();

    livewire(SitemapPage::class)
        ->assertSuccessful();
});
