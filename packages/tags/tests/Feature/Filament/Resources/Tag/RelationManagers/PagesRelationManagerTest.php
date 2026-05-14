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
    $tag = Tag::factory()->create();
    $matchingPage = Page::factory()->withTranslations()->create(['name' => 'Matching Relation Page']);
    $otherPage = Page::factory()->withTranslations()->create(['name' => 'Other Relation Page']);

    $tag->pages()->attach([$matchingPage->getKey(), $otherPage->getKey()]);

    $source = file_get_contents(dirname(__DIR__, 8) . '/../capell-4/packages/admin/src/Filament/Components/Tables/Columns/Page/PageNameColumn.php');

    expect($source)->toContain('->searchable()')
        ->and($tag->pages()->whereKey($matchingPage->getKey())->exists())->toBeTrue()
        ->and($tag->pages()->whereKey($otherPage->getKey())->exists())->toBeTrue();
});
