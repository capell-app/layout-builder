<?php

declare(strict_types=1);

use Capell\Core\Events\PageUrlChanged;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\DB;

it('builds a view-ready render model for current page and child active state', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $homePage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $secondaryPage = Page::factory()->site($site)->withTranslations()->create();
    $nestedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'meta' => ['component' => 'capell::stacked-list'],
        'items' => [
            [
                'label' => 'Home',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $homePage->id,
                    'pageable_type' => $homePage->getMorphClass(),
                ],
            ],
            [
                'label' => 'Parent',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/parent'],
                'children' => [
                    [
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $nestedPage->id,
                            'pageable_type' => $nestedPage->getMorphClass(),
                        ],
                    ],
                    [
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $secondaryPage->id,
                            'pageable_type' => $secondaryPage->getMorphClass(),
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $secondaryPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect($renderModel->items)->toHaveCount(2)
        ->and($renderModel->navigationKey)->toBe('main')
        ->and($renderModel->listComponent)->toBe('capell::stacked-list')
        ->and($renderModel->items[0]->label)->toBe('Home')
        ->and($renderModel->items[0]->url)->toBe($homePage->pageUrl->full_url)
        ->and($renderModel->items[0]->active)->toBeFalse()
        ->and($renderModel->items[1]->active)->toBeTrue()
        ->and($renderModel->items[1]->children[0]->url)->toBe($nestedPage->pageUrl->full_url)
        ->and($renderModel->items[1]->children[0]->active)->toBeFalse()
        ->and($renderModel->items[1]->children[1]->url)->toBe($secondaryPage->pageUrl->full_url)
        ->and($renderModel->items[1]->children[1]->active)->toBeTrue();
});

it('expands auto children using the provided site language and domain context', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'primary.test', 'path' => null])
        ->create();
    $otherSite = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'other.test', 'path' => null])
        ->create();
    $parentPage = Page::factory()->site($site)->withTranslations()->create();
    $currentChildPage = Page::factory()->site($site)->withTranslations()->parent($parentPage)->create();
    $otherChildPage = Page::factory()->site($otherSite)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $parentPage->id,
                    'pageable_type' => $parentPage->getMorphClass(),
                    'auto_children' => true,
                ],
            ],
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $otherChildPage->id,
                    'pageable_type' => $otherChildPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentChildPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect($renderModel->items)->toHaveCount(1)
        ->and($renderModel->items[0]->url)->toBe($parentPage->pageUrl->full_url)
        ->and($renderModel->items[0]->active)->toBeTrue()
        ->and($renderModel->items[0]->children)->toHaveCount(1)
        ->and($renderModel->items[0]->children[0]->label)->toBe($currentChildPage->translation->label)
        ->and($renderModel->items[0]->children[0]->url)->toBe($currentChildPage->pageUrl->full_url)
        ->and($renderModel->items[0]->children[0]->active)->toBeTrue()
        ->and($renderModel->items[0]->children[0]->data)->toHaveKey('url')
        ->and($renderModel->items[0]->children[0]->data)->not->toHaveKey('pageable_id')
        ->and($renderModel->items[0]->children[0]->data)->not->toHaveKey('pageable_type');
});

it('clears the page lookup cache through the render-model action', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $linkedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $linkedPage->id,
                    'pageable_type' => $linkedPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $context = new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    );

    BuildNavigationRenderModelAction::run($context);
    DB::table('page_urls')->where('id', $linkedPage->pageUrl->id)->update(['url' => '/changed']);

    $staleRenderModel = BuildNavigationRenderModelAction::run($context);
    BuildNavigationRenderModelAction::flushPageCache();
    $freshRenderModel = BuildNavigationRenderModelAction::run($context);

    expect($staleRenderModel->items[0]->url)->toBe($linkedPage->pageUrl->full_url)
        ->and($freshRenderModel->items[0]->url)->toContain('/changed');
});

it('renders heading items without a url', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'label' => 'Company',
                'type' => NavigationItemType::Heading->value,
                'data' => ['icon' => 'heroicon-o-building-office'],
            ],
            [
                'label' => 'About',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/about'],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect($renderModel->items)->toHaveCount(2)
        ->and($renderModel->items[0]->type)->toBe(NavigationItemType::Heading)
        ->and($renderModel->items[0]->label)->toBe('Company')
        ->and($renderModel->items[0]->url)->toBeNull()
        ->and($renderModel->items[0]->active)->toBeFalse()
        ->and($renderModel->items[1]->url)->toBe('/about');
});

it('flushes stale page lookup cache when a page url changed event is received', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $linkedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $linkedPage->id,
                    'pageable_type' => $linkedPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $context = new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    );

    BuildNavigationRenderModelAction::run($context);

    DB::table('page_urls')->where('id', $linkedPage->pageUrl->id)->update(['url' => '/event-changed']);

    event(new PageUrlChanged(
        page_url_id: (int) $linkedPage->pageUrl->getKey(),
        page_id: (int) $linkedPage->getKey(),
        site_id: (int) $site->getKey(),
        language_id: (int) $language->getKey(),
        old_url: $linkedPage->pageUrl->url,
        new_url: '/event-changed',
    ));

    $renderModel = BuildNavigationRenderModelAction::run($context);

    expect($renderModel->items[0]->url)->toContain('/event-changed');
});
