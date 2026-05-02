<?php

declare(strict_types=1);

use Capell\Core\Actions\SiteReplicatedAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

it('replicates a site with navigations and pages', function (): void {
    $languages = Language::factory()->count(2)->create();

    $site = Site::factory()
        ->language($languages->first() instanceof Language ? $languages->first() : Language::query()->find($languages->first()->id))
        ->state(['name' => 'Original'])
        ->create();

    $page = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->state([
            'items' => [
                [
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'label' => 'Page Link',
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    $clone = SiteReplicatedAction::run($site, [
        'copy_pages' => true,
        'copy_navigations' => true,
    ]);

    expect($clone)->toBeInstanceOf(Site::class)
        ->and($clone->id)->not()->toBe($site->id)
        ->and($clone->name)->toContain('Original');

    $clonedNavigations = $clone->navigations;
    $clonedPages = $clone->pages;

    expect($clonedNavigations)->toHaveCount(1)
        ->and($clonedNavigations->first())->not()->toBeNull()
        ->and($clonedNavigations->first()->site_id)->toBe($clone->id);

    expect($clonedPages)->toHaveCount(1)
        ->and($clonedPages->first())->not()->toBeNull();

    if ($clonedPages->first() !== null) {
        expect($clonedPages->first()->site_id)->toBe($clone->id);
    }

    $originalItem = $navigation->items[0];
    $clonedItem = $clonedNavigations->first()->items[0];

    expect($clonedItem->type)->toBe($originalItem->type)
        ->and($clonedItem->data['label'])->toBe('Page Link')
        ->and($clonedItem->data['pageable_id'])->toBe($clonedPages->first()?->id ?? null);
});

it('replicates only navigations when setup pages is used', function (): void {
    $language = Language::factory()->create();

    $site = Site::factory()->language($language)->state(['name' => 'Original'])->create();

    Navigation::factory()->site($site)->create();

    $defaultPages = CapellCore::getDefaultPages()->keys()->all();

    $clone = SiteReplicatedAction::run(
        $site,
        [
            'setup_pages' => true,
            'auto_create_pages' => $defaultPages,
            'copy_navigations' => true,
        ],
    );

    expect($clone)->toBeInstanceOf(Site::class)
        ->and($clone->id)->not()->toBe($site->id);

    expect($clone->navigations)->toHaveCount(1)
        ->and($clone->navigations->first()->site_id)->toBe($clone->id);

    expect($clone->pages)->not()->toBeEmpty();
});

it('does not copy navigations when disabled', function (): void {
    $site = Site::factory()->create();
    Navigation::factory()->site($site)->count(2)->create();

    $clone = SiteReplicatedAction::run($site, [
        'copy_pages' => true,
        'copy_navigations' => false,
    ]);

    expect($clone->navigations)->toBeEmpty();
});
