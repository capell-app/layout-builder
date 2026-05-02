<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Models\Article;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('page', 'article');

test('admin can see page articles', function (): void {
    test()->actingAsAdmin();

    get(ArticleResource::getUrl())
        ->assertOk();
});

test('cannot see page article', function (): void {
    test()->actingAsUser();

    get(ArticleResource::getUrl())
        ->assertForbidden();
});

test('admin can see create article', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->default()->create();

    Site::factory()
        ->has(SiteDomain::factory()->state(['language_id' => $language->id]))
        ->default()
        ->create();

    get(ArticleResource::getUrl('create'))
        ->assertOk();
});

test('admin can load edit article', function (): void {
    test()->actingAsAdmin();

    $page = Article::factory()->create();

    get(ArticleResource::getUrl('edit', ['record' => $page]))
        ->assertOk();
});
