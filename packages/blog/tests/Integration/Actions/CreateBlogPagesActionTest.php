<?php

declare(strict_types=1);

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Actions\InstallPackageAction;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\InstallPackageAction as LayoutBuilderInstallPackageAction;

beforeEach(function (): void {
    LayoutBuilderInstallPackageAction::run();
    InstallPackageAction::run();
});

it('creates the blog, archives, archive, tags, and tag pages for the site', function (): void {
    $site = Site::factory()->withTranslations()->create();

    CreateBlogPagesAction::run($site);

    $pages = Page::query()->where('site_id', $site->id)->with('type')->get();

    expect($pages)->not()->toBeEmpty();

    $pageTypeKeys = $pages->pluck('type.key')->filter()->unique()->values()->all();

    expect($pageTypeKeys)->toContain(BlogPageTypeEnum::Blog->value);
});

it('adds the archives widget to the results layout sidebar during install', function (): void {
    $resultsLayout = Layout::query()->firstWhere('key', LayoutEnum::Results->value);
    $sidebarWidgetKeys = array_column($resultsLayout->containers['sidebar']['widgets'], 'widget_key');

    expect($sidebarWidgetKeys)->toContain('archives');
});

it('creates an archive placeholder page under the archives parent', function (): void {
    $site = Site::factory()->withTranslations()->create();

    CreateBlogPagesAction::run($site);

    $archivePage = Page::query()
        ->where('site_id', $site->id)
        ->whereHas('type', fn ($query) => $query->where('key', BlogPageTypeEnum::Archive->value))
        ->first();

    expect($archivePage)->not()->toBeNull();
});

it('creates a tags page under the blog parent', function (): void {
    $site = Site::factory()->withTranslations()->create();

    CreateBlogPagesAction::run($site);

    $tagPage = Page::query()
        ->where('site_id', $site->id)
        ->whereHas('type', fn ($query) => $query->where('key', BlogPageTypeEnum::Tag->value))
        ->first();

    expect($tagPage)->not()->toBeNull();
});

it('is safe to run twice for the same site without duplicating the blog root page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    CreateBlogPagesAction::run($site);
    CreateBlogPagesAction::run($site);

    $blogPageCount = Page::query()
        ->where('site_id', $site->id)
        ->whereHas('type', fn ($query) => $query->where('key', BlogPageTypeEnum::Blog->value))
        ->count();

    expect($blogPageCount)->toBe(1);
});
