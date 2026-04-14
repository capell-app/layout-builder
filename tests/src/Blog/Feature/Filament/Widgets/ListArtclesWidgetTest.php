<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\ListArticlesWidget;
use Capell\Blog\Models\Article;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('article');

it('renders the articles widget', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations()->create();

    Page::factory()->site($site)->withTranslations()->create();

    Article::factory()->count(5)->site($site)->withTranslations()->create();

    livewire(ListArticlesWidget::class)
        ->assertOk()
        ->assertCountTableRecords(5);
});
