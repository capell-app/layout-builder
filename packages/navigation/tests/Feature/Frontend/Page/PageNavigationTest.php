<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

uses(TestingFrontend::class);

test('page renders without error when navigation exists with main handle', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Main,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)->assertOk();
});

test('page renders without error when navigation exists with footer handle', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Footer,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)->assertOk();
});

test('page renders without error when navigation exists with sub-footer handle', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::SubFooter,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)->assertOk();
});

test('navigation renders auto_children for page item without error', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->parent($parent)->state(['name' => 'Child 1'])->create();
    Page::factory()->site($site)->withTranslations()->parent($parent)->state(['name' => 'Child 2'])->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Main,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $parent->id,
                        'pageable_type' => $parent->getMorphClass(),
                        'auto_children' => true,
                    ],
                ],
            ],
        ])
        ->create();

    get($parent->pageUrl->full_url)->assertOk();
});
