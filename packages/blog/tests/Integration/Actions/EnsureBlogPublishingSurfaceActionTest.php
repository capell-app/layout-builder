<?php

declare(strict_types=1);

use Capell\Blog\Actions\EnsureArticlePublishingDefaultsAction;
use Capell\Blog\Actions\EnsureBlogPublishingSurfaceAction;
use Capell\Blog\Data\BlogPublishingSurfaceData;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\InstallPackageAction as LayoutBuilderInstallPackageAction;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function (): void {
    LayoutBuilderInstallPackageAction::run();
    EnsureArticlePublishingDefaultsAction::run();
});

it('creates the blog publishing surface with translations and urls', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $surface = EnsureBlogPublishingSurfaceAction::run($site);

    expect($surface)->toBeInstanceOf(BlogPublishingSurfaceData::class)
        ->and($surface->blogPage)->toBeInstanceOf(Page::class)
        ->and($surface->archivesPage->parent_id)->toBe($surface->blogPage->id)
        ->and($surface->archivePage->parent_id)->toBe($surface->archivesPage->id)
        ->and($surface->tagsPage->parent_id)->toBe($surface->blogPage->id)
        ->and($surface->tagPage->parent_id)->toBe($surface->tagsPage->id);

    $siteLanguages = $site->languages()->pluck('languages.id');

    foreach ([
        $surface->blogPage,
        $surface->archivesPage,
        $surface->archivePage,
        $surface->tagsPage,
        $surface->tagPage,
    ] as $page) {
        expect($page->translations()->whereIn('language_id', $siteLanguages)->count())->toBe($siteLanguages->count())
            ->and($page->pageUrls()->whereIn('language_id', $siteLanguages)->count())->toBe($siteLanguages->count());
    }
});

it('creates blog archive and tag pages with the expected urls', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $surface = EnsureBlogPublishingSurfaceAction::run($site);

    expect($surface->blogPage->pageUrls()->pluck('url')->all())->toContain('/blog')
        ->and($surface->archivesPage->pageUrls()->pluck('url')->all())->toContain('/blog/archives')
        ->and($surface->archivePage->pageUrls()->pluck('url')->all())->toContain('/blog/archives/*')
        ->and($surface->tagsPage->pageUrls()->pluck('url')->all())->toContain('/blog/tags')
        ->and($surface->tagPage->pageUrls()->pluck('url')->all())->toContain('/blog/tags/*');
});

it('links the blog page into main and footer navigation', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->languages()->firstOrFail();

    $mainNavigation = Navigation::factory()->site($site)->language($language)->create([
        'key' => NavigationHandle::Main->value,
    ]);
    $footerNavigation = Navigation::factory()->site($site)->language($language)->create([
        'key' => NavigationHandle::Footer->value,
    ]);

    $surface = EnsureBlogPublishingSurfaceAction::run($site);

    $mainNavigation->refresh();
    $footerNavigation->refresh();

    expect($mainNavigation->items->toCollection()->pluck('data.pageable_id'))->toContain($surface->blogPage->id)
        ->and($mainNavigation->items->toCollection()->pluck('type'))->toContain(NavigationItemType::Page)
        ->and($footerNavigation->items->toCollection()->pluck('data.pageable_id'))->toContain($surface->blogPage->id)
        ->and($footerNavigation->items->toCollection()->pluck('type'))->toContain(NavigationItemType::Page);
});

it('is idempotent for a site', function (): void {
    $site = Site::factory()->withTranslations()->create();

    EnsureBlogPublishingSurfaceAction::run($site);
    EnsureBlogPublishingSurfaceAction::run($site);

    expect(Page::query()
        ->where('site_id', $site->id)
        ->whereHas('type', fn (Builder $query): Builder => $query->where('key', BlogPageTypeEnum::Blog->value))
        ->count())->toBe(1)
        ->and(Page::query()
            ->where('site_id', $site->id)
            ->whereHas('type', fn (Builder $query): Builder => $query->where('key', BlogPageTypeEnum::Archive->value))
            ->count())->toBe(1)
        ->and(Page::query()
            ->where('site_id', $site->id)
            ->whereHas('type', fn (Builder $query): Builder => $query->where('key', BlogPageTypeEnum::Tag->value))
            ->count())->toBe(1);
});
