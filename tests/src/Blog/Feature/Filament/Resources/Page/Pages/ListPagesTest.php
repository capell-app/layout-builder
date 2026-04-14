<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\Blog\Models\Article;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list pages', function (): void {
    Type::factory()->page()->create();

    Article::factory()->create();

    $pages = Page::factory()->count(5)->create();

    livewire(ListPages::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($pages);
});
