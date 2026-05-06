<?php

declare(strict_types=1);

use Capell\Blog\Actions\EnsureArticlePublishingDefaultsAction;
use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Actions\InstallPackageAction as LayoutBuilderInstallPackageAction;
use Capell\LayoutBuilder\Models\Widget;

beforeEach(function (): void {
    LayoutBuilderInstallPackageAction::run();
});

it('installs article publishing page types layouts and widgets', function (): void {
    EnsureArticlePublishingDefaultsAction::run();

    expect(Type::query()->pageType()->where('key', BlogPageTypeEnum::Article->value)->exists())->toBeTrue()
        ->and(Type::query()->pageType()->where('key', BlogPageTypeEnum::Blog->value)->exists())->toBeTrue()
        ->and(Type::query()->pageType()->where('key', BlogPageTypeEnum::Archive->value)->exists())->toBeTrue()
        ->and(Type::query()->pageType()->where('key', BlogPageTypeEnum::Tag->value)->exists())->toBeTrue()
        ->and(Layout::query()->where('key', BlogLayoutEnum::Article->value)->exists())->toBeTrue()
        ->and(Layout::query()->where('key', BlogLayoutEnum::BlogPage->value)->exists())->toBeTrue()
        ->and(Layout::query()->where('key', BlogLayoutEnum::Archives->value)->exists())->toBeTrue()
        ->and(Layout::query()->where('key', BlogLayoutEnum::Tags->value)->exists())->toBeTrue()
        ->and(Widget::query()->where('key', 'article')->exists())->toBeTrue()
        ->and(Widget::query()->where('key', 'latest-articles')->exists())->toBeTrue()
        ->and(Widget::query()->where('key', 'archives')->exists())->toBeTrue()
        ->and(Widget::query()->where('key', 'tags')->exists())->toBeTrue()
        ->and(Widget::query()->where('key', 'related-pages')->exists())->toBeTrue();
});

it('updates default and results sidebars with article publishing widgets', function (): void {
    EnsureArticlePublishingDefaultsAction::run();

    $defaultLayout = Layout::query()->firstWhere('key', LayoutEnum::Default->value);
    $resultsLayout = Layout::query()->firstWhere('key', LayoutEnum::Results->value);

    $defaultSidebarWidgetKeys = array_column($defaultLayout->containers['sidebar']['widgets'], 'widget_key');
    $resultsSidebarWidgetKeys = array_column($resultsLayout->containers['sidebar']['widgets'], 'widget_key');

    expect($defaultSidebarWidgetKeys)->toContain('latest-articles')
        ->and($defaultSidebarWidgetKeys)->not->toContain('latest-pages')
        ->and($resultsSidebarWidgetKeys)->toContain('latest-articles')
        ->and($resultsSidebarWidgetKeys)->toContain('archives')
        ->and($resultsSidebarWidgetKeys)->not->toContain('latest-pages');
});
