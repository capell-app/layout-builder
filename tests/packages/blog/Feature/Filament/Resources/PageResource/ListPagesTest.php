<?php

declare(strict_types=1);

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\PageResource\Pages\ListPages;
use Capell\Blog\Database\Factories\ArticlePageFactory;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Filament\Resources\ArticleResource;
use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list pages', function (): void {
    CapellAdmin::registerResource(
        ResourceEnum::Page,
        class: ArticleResource::class,
        name: BlogResourceEnum::Article->name,
    );

    (new ArticlePageFactory())->create();

    $pages = Page::factory()->count(5)->create();

    livewire(ListPages::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($pages);
});
