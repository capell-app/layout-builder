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
