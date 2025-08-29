<?php

declare(strict_types=1);

use Capell\Blog\Database\Factories\ArticlePageFactory;
use Capell\Blog\Filament\Resources\Articles\Pages\ListArticles;
use Capell\Core\Models\Page;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page', 'article');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list articles', function (): void {
    Page::factory()->withTranslations()->count(5)->create();

    $pages = (new ArticlePageFactory)->withTranslations()->count(5)->create();

    livewire(ListArticles::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($pages);
});
