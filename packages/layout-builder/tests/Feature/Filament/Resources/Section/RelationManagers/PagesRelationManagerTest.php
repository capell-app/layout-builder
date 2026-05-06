<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Resources\Sections\Pages\EditSection;
use Capell\LayoutBuilder\Filament\Resources\Sections\RelationManagers\PagesRelationManager;
use Capell\LayoutBuilder\Models\Section;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;

use function Pest\Livewire\livewire;

it('can list pages for a content model', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $content = Section::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($content)
                ->state(['container' => 'main'])
                ->forEachSequence(
                    ['occurrence' => 1],
                    ['occurrence' => 2],
                ),
            'assets',
        )
        ->create();

    $widgetAsset = $widget->assets()->first();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($content->pages)
        ->assertTableColumnStateSet('pageable.name', [$page->name], record: $widgetAsset);
});

it('can search pages for a content model', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $content = Section::factory()->create();
    Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($content)
                ->state(['container' => 'main'])
                ->sequence(
                    ['occurrence' => 1],
                    ['occurrence' => 2],
                    ['occurrence' => 3],
                    ['occurrence' => 4],
                    ['occurrence' => 5],
                )
                ->count(5),
            'assets',
        )
        ->create();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->searchTable($page->getKey())
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$page->getMorphClass() . '-' . $page->getKey()]);
});

it('returns no results when search matches nothing', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $content = Section::factory()->create();

    Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($content)
                ->state(['container' => 'main', 'occurrence' => 1]),
            'assets',
        )
        ->create();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->searchTable('99999999')
        ->assertCountTableRecords(0);
});
