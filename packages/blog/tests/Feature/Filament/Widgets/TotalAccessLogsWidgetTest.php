<?php

declare(strict_types=1);

use Capell\Admin\Filament\Widgets\Health\TotalPageViewsWidget;
use Capell\Blog\Models\Article;
use Capell\Core\Models\PageView;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('access-logs');

it('renders the access logs widget', function (): void {
    test()->actingAsAdmin();

    $article = Article::factory()->create();

    $accessLog = PageView::factory()->page($article)->create();

    livewire(TotalPageViewsWidget::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$accessLog]);
});
