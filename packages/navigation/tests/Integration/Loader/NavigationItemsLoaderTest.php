<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;

it('loads morph navigation items and preserves order', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $secondaryPage = Page::factory()->site($site)->withTranslations()->create();
    $nestedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
    ]);

    $navigation->items = [
        [
            'label' => 'External',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => 'http://example.com'],
        ],
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $secondaryPage->id,
                'pageable_type' => $secondaryPage->getMorphClass(),
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
                        'pageable_id' => $currentPage->id,
                        'pageable_type' => $currentPage->getMorphClass(),
                    ],
                ],
            ],
        ],
    ];

    $domain = $site->siteDomains->first();

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $domain,
    );

    $items = $loader->fetchMenuItems();

    $loader->activeMenuItems($items);

    expect($items)->toHaveCount(3)
        ->and($items[0]->type->value)->toBe(NavigationItemType::Link->value)
        ->and($items[1]->type->value)->toBe(NavigationItemType::Page->value)
        ->and($items[1]->data['pageable_id'])->toBe($secondaryPage->id)
        ->and($items[1]->data['url'])->toBe($secondaryPage->pageUrl->full_url)
        ->and($items[2]->children[0]->data['pageable_id'])->toBe($nestedPage->id)
        ->and($items[2]->children[0]->data['url'])->toBe($nestedPage->pageUrl->full_url)
        ->and($items[2]->children[1]->data['pageable_id'])->toBe($currentPage->id)
        ->and($items[2]->children[1]->active)->toBeTrue()
        ->and($items[2]->active)->toBeTrue();
});

it('loads pure link navigation items', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
        'items' => [
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/docs'],
            ],
        ],
    ]);

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $site->siteDomains->first(),
    );

    $items = $loader->fetchMenuItems();

    expect($items)->toHaveCount(1)
        ->and($items[0]->type->value)->toBe(NavigationItemType::Link->value)
        ->and($items[0]->data['url'])->toBe('/docs');
});

it('skips page items that belong to a different site', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $otherSite = Site::factory()->language($language)->withTranslations()->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $otherSitePage = Page::factory()->site($otherSite)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $otherSitePage->id,
                    'pageable_type' => $otherSitePage->getMorphClass(),
                ],
            ],
            [
                'label' => 'Safe Link',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/safe'],
            ],
        ],
    ]);

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $site->siteDomains->first(),
    );

    $items = $loader->fetchMenuItems();

    expect($items)->toHaveCount(1)
        ->and($items[0]->label)->toBe('Safe Link');
});

it('excludes hidden navigation items and hidden nested children', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
        'items' => [
            [
                'label' => 'Visible',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/visible'],
            ],
            [
                'label' => 'Hidden',
                'type' => NavigationItemType::Link->value,
                'is_visible' => false,
                'data' => ['url' => '/hidden'],
                'children' => [
                    [
                        'label' => 'Hidden Child Subtree',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/hidden-child-subtree'],
                    ],
                ],
            ],
            [
                'label' => 'Visible Parent',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/visible-parent'],
                'children' => [
                    [
                        'label' => 'Nested Hidden',
                        'type' => NavigationItemType::Link->value,
                        'is_visible' => false,
                        'data' => ['url' => '/nested-hidden'],
                    ],
                    [
                        'label' => 'Nested Visible',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/nested-visible'],
                    ],
                ],
            ],
        ],
    ]);

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $site->siteDomains->first(),
    );

    $items = $loader->fetchMenuItems();

    expect($items)->toHaveCount(2)
        ->and($items->pluck('label')->all())->toBe(['Visible', 'Visible Parent'])
        ->and($items[1]->children)->toHaveCount(1)
        ->and($items[1]->children[0]->label)->toBe('Nested Visible');
});
