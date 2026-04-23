<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tags\Filament\Resources\Tags\Pages\EditTag;
use Capell\Tags\Filament\Resources\Tags\RelationManagers\PagesRelationManager;
use Capell\Tags\Models\Tag;

use function Pest\Livewire\livewire;

it('can list pages for a tag', function (): void {
    $tag = Tag::factory()
        ->has(Page::factory()->withTranslations()->count(5), 'pages')
        ->create();

    $page = $tag->pages->first();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $tag,
        'pageClass' => EditTag::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($tag->pages)
        ->assertTableColumnStateSet('name', [$page->name], record: $page);
});

it('can search pages for a tag', function (): void {
    $tag = Tag::factory()
        ->has(Page::factory()->withTranslations()->count(5), 'pages')
        ->create();

    $page = $tag->pages->random();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $tag,
        'pageClass' => EditTag::class,
    ])
        ->assertSuccessful()
        ->searchTable($page->getKey())
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$page]);
});
