<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\TypeCreator;
use Capell\DemoKit\Support\Creator\DemoCreator;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

use function Pest\Laravel\assertDatabaseHas;

it('sets up main, footer, and sub-footer navigation via DemoCreator', function (): void {
    $demoCreator = new DemoCreator;

    $language = Language::factory()->default()->create();

    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();
    $demoCreator->setupSite($site);

    $homePage = $demoCreator->createPage([
        'name' => ['en' => 'Home'],
        'title' => ['en' => 'Home'],
    ], $site, createMedia: false);
    assert($homePage instanceof Page);

    $demoCreator->setupMainNavigation($site, $language, $homePage);
    $demoCreator->setupFooterNavigation($site, $language);
    $demoCreator->subFooterNavigation($site, $language);

    expect($site->navigations()->count())->toBeGreaterThanOrEqual(1);
});

it('merges generated footer items into an existing navigation with persisted items', function (): void {
    $demoCreator = new DemoCreator;

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

    $navigationType->update(['default' => true, 'status' => true]);
    $defaultPageType->update(['default' => true, 'status' => true]);

    $parentPage = Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->withTranslations(collect([$language]), ['title' => 'About', 'label' => 'About'])
        ->create(['name' => 'About', 'visible_from' => now()->subDay()]);

    Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->parent($parentPage)
        ->withTranslations(collect([$language]), ['title' => 'Team', 'label' => 'Team'])
        ->create(['name' => 'Team', 'visible_from' => now()->subDay()]);

    Navigation::factory()->site($site)->language($language)->create([
        'key' => NavigationHandle::Footer->value,
        'type_id' => $navigationType->id,
        'items' => [
            'existing-link' => [
                'label' => 'Existing Link',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => 'https://example.com/existing'],
                'children' => [],
            ],
        ],
    ]);

    $demoCreator->setupFooterNavigation($site, $language);

    $navigation = Navigation::query()
        ->where('key', NavigationHandle::Footer->value)
        ->where('site_id', $site->id)
        ->where('language_id', $language->id)
        ->first();

    expect($navigation)->toBeInstanceOf(Navigation::class)
        ->and($navigation->items)->not()->toBeNull();

    $items = collect($navigation->items?->toArray())->values();
    $labels = $items->pluck('label')->all();

    expect($items)->toHaveCount(2)
        ->and($labels)->toContain('Existing Link', 'About');

    $aboutItem = $items->firstWhere('label', 'About');

    expect($aboutItem)->not()->toBeNull()
        ->and($aboutItem)->toMatchArray([
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $parentPage->getKey(),
                'pageable_type' => $parentPage->getMorphClass(),
            ],
        ]);
});

it('creates main navigation with home page and eligible nested pages only', function (): void {
    $demoCreator = new DemoCreator;

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

    $navigationType->update(['default' => true, 'status' => true]);
    $defaultPageType->update(['default' => true, 'status' => true]);
    $homePageType->update(['status' => true]);

    $createPublishedPage = function (string $name, array $translationData = [], ?Page $parentPage = null) use ($site, $language, $defaultPageType): Page {
        $factory = Page::factory()->site($site)->type($defaultPageType);
        if ($parentPage instanceof Page) {
            $factory = $factory->parent($parentPage);
        }

        return $factory
            ->withTranslations(collect([$language]), ['title' => $name, 'label' => $name, ...$translationData])
            ->create(['name' => $name, 'visible_from' => now()->subDay()]);
    };

    $home = Page::factory()
        ->site($site)
        ->type($homePageType)
        ->withTranslations(collect([$language]), ['title' => 'Home', 'label' => 'Home'])
        ->create(['name' => 'Home', 'visible_from' => now()->subDay()]);

    $aboutPage = $createPublishedPage('About');
    $createPublishedPage('Team', [], $aboutPage);

    $servicesPage = $createPublishedPage('Services');
    $createPublishedPage('Consulting', [], $servicesPage);

    $createPublishedPage('Standalone');

    Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->withTranslations(collect([$language]), ['title' => 'Draft Section', 'label' => 'Draft Section'])
        ->create(['name' => 'Draft Section', 'visible_from' => now()->addDay()]);

    $demoCreator->setupMainNavigation($site, $language, $home);

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

    $items = collect($navigation->items?->toArray())->values();
    $labels = $items->pluck('label')->all();

    expect($items)->toHaveCount(3)
        ->and($items->first())->toMatchArray([
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
        ->and($labels)->toContain('Home', 'About', 'Services')
        ->not()->toContain('Standalone', 'Draft Section');

    $aboutItem = $items->firstWhere('label', 'About');
    $servicesItem = $items->firstWhere('label', 'Services');

    expect($aboutItem)->not()->toBeNull()
        ->and(collect($aboutItem['children'])->values()->pluck('label')->all())->toContain('Team')
        ->and($servicesItem)->not()->toBeNull()
        ->and(collect($servicesItem['children'])->values()->pluck('label')->all())->toContain('Consulting');
});
