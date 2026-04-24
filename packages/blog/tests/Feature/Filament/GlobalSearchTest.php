<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Models\Article;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tags\Models\Tag;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;

uses(CreatesAdminUser::class)
    ->group('global-search');

beforeEach(function (): void {
    test()->actingAsAdmin();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();
});

it('finds an article by its $attribute', function (string $searchTerm): void {
    $articleNameToken = 'capell-blog-article-name-token';
    $articleTitleToken = 'capell-blog-article-title-token';
    $articleUrlToken = 'capell-blog-article-url-token';

    $language = Language::factory()->create();

    $site = Site::factory()->language($language)->withTranslations($language)->create([
        'language_id' => $language->id,
    ]);

    $article = Article::factory()->site($site)->create([
        'name' => $articleNameToken,
    ]);

    $article->translations()->create([
        'language_id' => $language->id,
        'title' => $articleTitleToken,
    ]);

    $article->pageUrls()->create([
        'site_id' => $site->id,
        'language_id' => $language->id,
        'url' => '/blog/' . $articleUrlToken,
    ]);

    $results = Filament::getGlobalSearchProvider()->getResults($searchTerm);
    $articleResult = $results?->getCategories()->get(ArticleResource::getPluralModelLabel())?->first();

    expect($articleResult)
        ->toBeInstanceOf(GlobalSearchResult::class)
        ->and($articleResult->title)->toBe($article->name)
        ->and($articleResult->url)->toBe(ArticleResource::getUrl('edit', ['record' => $article]));
})->with([
    'name' => ['capell-blog-article-name-token'],
    'title' => ['capell-blog-article-title-token'],
    'url' => ['capell-blog-article-url-token'],
]);

it('finds a tag by its $attribute', function (string $searchTerm): void {
    $tagNameToken = 'capell-blog-tag-name-token';

    $tag = Tag::factory()->create([
        'name' => ['en' => $tagNameToken],
        'slug' => ['en' => $tagNameToken],
    ]);

    $results = Filament::getGlobalSearchProvider()->getResults($searchTerm);
    $tagResult = $results?->getCategories()->get(TagResource::getPluralModelLabel())?->first();

    expect($tagResult)
        ->toBeInstanceOf(GlobalSearchResult::class)
        ->and($tagResult->title)->toBe($tagNameToken)
        ->and($tagResult->url)->toBe(TagResource::getUrl('edit', ['record' => $tag]));
})->with([
    'name' => ['capell-blog-tag-name-token'],
]);
