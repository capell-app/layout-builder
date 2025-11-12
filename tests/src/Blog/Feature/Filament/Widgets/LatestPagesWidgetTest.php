<?php

declare(strict_types=1);

use Capell\Admin\Filament\Widgets\LatestPagesWidget;
use Capell\Blog\Database\Factories\ArticlePageFactory;
use Capell\Core\Models\Page;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

it('renders the pages widget', function (): void {
    test()->actingAsAdmin();

    Page::factory(5)->create();

    (new ArticlePageFactory)->count(5)->create();

    livewire(LatestPagesWidget::class)
        ->assertOk()
        ->assertCountTableRecords(10);
});
