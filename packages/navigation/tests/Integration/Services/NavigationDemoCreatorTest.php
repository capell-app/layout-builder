<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\TypeCreator;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;

use function Pest\Laravel\assertDatabaseHas;

it('sets up main, footer, and sub-footer navigation', function (): void {
    $navigationDemoCreator = resolve(NavigationDemoCreator::class);

    $language = Language::factory()->default()->create();

    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();

    $typeCreator = resolve(TypeCreator::class);
    $navigationType = $typeCreator->createNavigationType();
    $homePageType = $typeCreator->homePageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);

    $homePageType->update([
        'status' => true,
    ]);

    $homePage = Page::factory()
        ->site($site)
        ->type($homePageType)
        ->withTranslations(collect([$language]), [
            'title' => 'Home',
            'label' => 'Home',
        ])
        ->create([
            'name' => 'Home',
            'visible_from' => now()->subDay(),
        ]);

    $navigationDemoCreator->setupMainNavigation($site, $language, $homePage);
    $navigationDemoCreator->setupFooterNavigation($site, $language);
    $navigationDemoCreator->setupSubFooterNavigation($site, $language);

    expect($site->navigations()->count())->toBeGreaterThanOrEqual(1);
});

it('merges generated footer items into an existing navigation with persisted items', function (): void {
    $navigationDemoCreator = resolve(NavigationDemoCreator::class);

    $language = Language::factory()->english()->create();

    Site::factory()->default()->create();

    $site = Site::factory()
        ->language($language)
        ->withTranslations($language)
        ->create([
            'default' => false,
            'name' => 'Footer Demo Site',
        ]);

    $typeCreator = resolve(TypeCreator::class);
    $navigationType = $typeCreator->createNavigationType();
    $defaultPageType = $typeCreator->defaultPageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);

    $defaultPageType->update([
        'default' => true,
        'status' => true,
    ]);

    $parentPage = Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->withTranslations(collect([$language]), [
            'title' => 'About',
            'label' => 'About',
        ])
        ->create([
            'name' => 'About',
            'visible_from' => now()->subDay(),
        ]);

    Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->parent($parentPage)
        ->withTranslations(collect([$language]), [
            'title' => 'Team',
            'label' => 'Team',
        ])
        ->create([
            'name' => 'Team',
            'visible_from' => now()->subDay(),
        ]);

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->create([
            'key' => NavigationHandle::Footer->value,
            'type_id' => $navigationType->id,
            'items' => [
                'existing-link' => [
                    'label' => 'Existing Link',
                    'type' => NavigationItemType::Link->value,
                    'data' => [
                        'url' => 'https://example.com/existing',
                    ],
                    'children' => [],
                ],
            ],
        ]);

    $navigationDemoCreator->setupFooterNavigation($site, $language);

    $navigation = Navigation::query()
        ->where('key', NavigationHandle::Footer->value)
        ->where('site_id', $site->id)
        ->where('language_id', $language->id)
        ->first();

    expect($navigation)->toBeInstanceOf(Navigation::class)
        ->and($navigation->items)->not()->toBeNull();

    $navigationItems = collect($navigation->items?->toArray())->values();
    $navigationLabels = $navigationItems->pluck('label')->all();

    expect($navigationItems)->toHaveCount(2)
        ->and($navigationLabels)
        ->toContain('Existing Link', 'About');

    $aboutNavigationItem = $navigationItems->firstWhere('label', 'About');

    expect($aboutNavigationItem)->not()->toBeNull()
        ->and($aboutNavigationItem)
        ->toMatchArray([
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $parentPage->getKey(),
                'pageable_type' => $parentPage->getMorphClass(),
            ],
        ]);
});

it('creates main navigation with the home page and eligible nested pages only', function (): void {
    $navigationDemoCreator = resolve(NavigationDemoCreator::class);

    $language = Language::factory()->english()->create();

    $site = Site::factory()
        ->language($language)
        ->default()
        ->withTranslations($language)
        ->create(['name' => 'Demo Site']);

    $typeCreator = resolve(TypeCreator::class);
    $navigationType = $typeCreator->createNavigationType();
    $defaultPageType = $typeCreator->defaultPageType();
    $homePageType = $typeCreator->homePageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);

    $defaultPageType->update([
        'default' => true,
        'status' => true,
    ]);

    $homePageType->update([
        'status' => true,
    ]);

    $createPublishedPage = function (string $name, array $translationData = [], ?Page $parentPage = null) use ($site, $language, $defaultPageType): Page {
        $pageFactory = Page::factory()
            ->site($site)
            ->type($defaultPageType);

        if ($parentPage instanceof Page) {
            $pageFactory = $pageFactory->parent($parentPage);
        }

        return $pageFactory->withTranslations(
            collect([$language]),
            [
                'title' => $name,
                'label' => $name,
                ...$translationData,
            ],
        )
            ->create([
                'name' => $name,
                'visible_from' => now()->subDay(),
            ]);
    };

    $home = Page::factory()
        ->site($site)
        ->type($homePageType)
        ->withTranslations(collect([$language]), [
            'title' => 'Home',
            'label' => 'Home',
        ])
        ->create([
            'name' => 'Home',
            'visible_from' => now()->subDay(),
        ]);

    $aboutPage = $createPublishedPage('About');
    $createPublishedPage('Team', [], $aboutPage);

    $servicesPage = $createPublishedPage('Services');
    $createPublishedPage('Consulting', [], $servicesPage);

    $createPublishedPage('Standalone');

    $draftPage = Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->withTranslations(collect([$language]), [
            'title' => 'Draft Section',
            'label' => 'Draft Section',
        ])
        ->create([
            'name' => 'Draft Section',
            'visible_from' => now()->addDay(),
        ]);

    $createPublishedPage('Hidden Draft Child', [], $draftPage);

    $navigationDemoCreator->setupMainNavigation($site, $language, $home);

    assertDatabaseHas('navigations', [
        'key' => NavigationHandle::Main->value,
        'site_id' => $site->id,
        'language_id' => $language->id,
    ]);

    $navigation = Navigation::query()
        ->where('key', NavigationHandle::Main->value)
        ->where('site_id', $site->id)
        ->where('language_id', $language->id)
        ->first();

    expect($navigation)->toBeInstanceOf(Navigation::class)
        ->and($navigation->items)->not()->toBeNull();

    $navigationItems = collect($navigation->items?->toArray())->values();
    $navigationLabels = $navigationItems->pluck('label')->all();

    expect($navigationItems)->toHaveCount(3)
        ->and($navigationItems->first())
        ->toMatchArray([
            'label' => 'Home',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $home->getKey(),
                'pageable_type' => $home->getMorphClass(),
                'hidden_label' => true,
                'icon' => 'heroicon-o-home',
            ],
        ])
        ->and($navigationLabels)
        ->toContain('Home', 'About', 'Services')
        ->not()->toContain('Standalone', 'Draft Section');

    $aboutNavigationItem = $navigationItems->firstWhere('label', 'About');
    $servicesNavigationItem = $navigationItems->firstWhere('label', 'Services');

    expect($aboutNavigationItem)->not()->toBeNull()
        ->and(collect($aboutNavigationItem['children'])->values()->pluck('label')->all())
        ->toContain('Team')
        ->and($servicesNavigationItem)->not()->toBeNull()
        ->and(collect($servicesNavigationItem['children'])->values()->pluck('label')->all())
        ->toContain('Consulting');
});
