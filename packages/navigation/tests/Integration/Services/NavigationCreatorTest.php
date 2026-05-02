<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Creator\NavigationCreator;

it('does not duplicate pages when additional items already reference the same page', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->for($site)->create();
    $creator = new NavigationCreator;

    $additionalItems = [
        [
            'label' => 'Existing',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
            ],
            'children' => [],
        ],
    ];

    $navigation = $creator->footerNavigation($site, null, null, collect([$page]), $additionalItems);

    $matches = collect($navigation->items)->filter(
        fn (array $item): bool => isset($item['data']['pageable_id'], $item['data']['pageable_type'])
            && (int) $item['data']['pageable_id'] === $page->getKey()
            && $item['data']['pageable_type'] === $page->getMorphClass(),
    );

    expect($matches->count())->toBe(1);
});

it('does not prepend duplicate home when same page exists in navigation items', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->for($site)->create();
    $creator = new NavigationCreator;

    $additionalItems = [
        [
            'label' => 'HomeLike',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
            ],
            'children' => [],
        ],
    ];

    $navigation = $creator->mainNavigation($site, null, null, $page, $additionalItems);

    $matches = collect($navigation->items)->filter(
        fn (array $item): bool => isset($item['data']['pageable_id'], $item['data']['pageable_type'])
            && (int) $item['data']['pageable_id'] === $page->getKey()
            && $item['data']['pageable_type'] === $page->getMorphClass(),
    );

    expect($matches->count())->toBe(1);
});

it('returns translation label when language provided', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations(collect([$language]))->create();

    $label = NavigationCreator::getPageNavigationLabel($page, $language);

    $translation = $page->translations->firstWhere('language_id', $language->id);

    expect($label)->toBe($translation->label);
});

it('creates footer navigation and adds pages when missing', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->for($site)->create();
    $creator = new NavigationCreator;

    $navigation = $creator->footerNavigation($site, null, null, collect([$page]), []);

    expect($navigation)->toBeInstanceOf(Navigation::class);

    $matches = collect($navigation->items)->filter(
        fn (array $item): bool => isset($item['data']['pageable_id'], $item['data']['pageable_type'])
            && (int) $item['data']['pageable_id'] === $page->getKey()
            && $item['data']['pageable_type'] === $page->getMorphClass(),
    );

    expect($matches->count())->toBe(1);
});

it('subFooterNavigation is an alias of footerNavigation', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->for($site)->create();
    $creator = new NavigationCreator;

    $sub = $creator->subFooterNavigation($site, null, null, collect([$page]), []);

    expect($sub->key)->toBe(NavigationHandle::SubFooter->value)
        ->and(collect($sub->items)->count())->toBeGreaterThanOrEqual(1);
});

it('prepends home to main navigation when not present', function (): void {
    $site = Site::factory()->create();
    $home = Page::factory()->for($site)->create();
    $creator = new NavigationCreator;

    $navigation = $creator->mainNavigation($site, null, null, $home, []);

    $first = collect($navigation->items)->first();

    expect($first['data']['hidden_label'])->toBeTrue()
        ->and($first['data']['pageable_id'])->toBe($home->getKey());
});

it('adds additionalItems when not duplicates', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->for($site)->create();
    $creator = new NavigationCreator;

    $additionalItems = [
        [
            'label' => 'Extra',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
            ],
            'children' => [],
        ],
    ];

    $navigation = $creator->mainNavigation($site, null, null, null, $additionalItems);

    $matches = collect($navigation->items)->filter(
        fn (array $item): bool => isset($item['data']['pageable_id'], $item['data']['pageable_type'])
            && (int) $item['data']['pageable_id'] === $page->getKey()
            && $item['data']['pageable_type'] === $page->getMorphClass(),
    );

    expect($matches->count())->toBe(1);
});

it('reuses existing navigation when called twice', function (): void {
    $site = Site::factory()->create();
    $creator = new NavigationCreator;

    $first = $creator->footerNavigation($site, null, null, null, []);
    $second = $creator->footerNavigation($site, null, null, null, []);

    expect($first->id)->toBe($second->id);
});
