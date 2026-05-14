<?php

declare(strict_types=1);

use Capell\Blog\Actions\InstallPackageAction;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\CacheEnum;
use Capell\Blog\Models\Article;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Actions\InstallPackageAction as LayoutBuilderInstallPackageAction;
use Capell\Core\Models\Type;

beforeEach(function (): void {
    LayoutBuilderInstallPackageAction::run();
    InstallPackageAction::run();
});

it('returns false for hasPageHierarchy', function (): void {
    expect(Article::hasPageHierarchy())->toBeFalse();
});

it('returns Latest for defaultOrdering', function (): void {
    expect(Article::defaultOrdering())->toBe(PageOrderEnum::Latest);
});

it('returns the article page type for getDefaultType', function (): void {
    $type = Article::getDefaultType(null);

    expect($type)->not()->toBeNull()
        ->and($type->key)->toBe(BlogPageTypeEnum::Article->value);
});

it('returns null for getDefaultType when the article type does not exist', function (): void {
    Type::query()->where('key', BlogPageTypeEnum::Article->value)->delete();

    expect(Article::getDefaultType(null))->toBeNull();
});

it('returns the publish date from visible_from when set', function (): void {
    $article = Article::factory()->create(['visible_from' => '2025-03-15 00:00:00']);

    $publishDate = $article->getPublishDate();

    expect($publishDate)->not()->toBeNull()
        ->and($publishDate->format('Y-m-d'))->toBe('2025-03-15');
});

it('falls back to created_at for publish date when visible_from is null', function (): void {
    $article = Article::factory()->create([
        'visible_from' => null,
        'created_at' => '2025-01-10 12:00:00',
    ]);

    $publishDate = $article->getPublishDate();

    expect($publishDate)->not()->toBeNull()
        ->and($publishDate->format('Y-m-d'))->toBe('2025-01-10');
});

it('returns an empty collection for draftRevisions', function (): void {
    $article = Article::factory()->create();

    expect($article->draftRevisions)->toBeEmpty();
});

it('shouldLogVisit returns true when disable_visit_logs is set to true in type meta', function (): void {
    $type = Type::factory()->page()->create(['meta' => ['disable_visit_logs' => true]]);
    $article = Article::factory()->type($type)->create();

    expect($article->shouldLogVisit())->toBeTrue();
});

it('shouldLogVisit returns true when disable_visit_logs is absent from type meta', function (): void {
    $type = Type::factory()->page()->create(['meta' => []]);
    $article = Article::factory()->type($type)->create();

    expect($article->shouldLogVisit())->toBeTrue();
});

it('shouldLogVisit returns false when disable_visit_logs is set to false in type meta', function (): void {
    $type = Type::factory()->page()->create(['meta' => ['disable_visit_logs' => false]]);
    $article = Article::factory()->type($type)->create();

    expect($article->shouldLogVisit())->toBeFalse();
});

it('clears cached blog content when articles are saved', function (): void {
    $article = Article::factory()->create(['name' => 'Original article']);
    $cacheKey = CacheEnum::blogPage((int) $article->site_id, 'null', BlogPageTypeEnum::Blog->value);
    $unrelatedCacheKey = 'capell-blog-unrelated-content-test';

    CapellCore::setToCache($cacheKey, 'stale');
    CapellCore::setToCache($unrelatedCacheKey, 'fresh');

    expect(CapellCore::cacheExists($cacheKey))->toBeTrue()
        ->and(CapellCore::cacheExists($unrelatedCacheKey))->toBeTrue();

    $article->forceFill(['name' => 'Updated article'])->save();

    expect(CapellCore::cacheExists($cacheKey))->toBeFalse()
        ->and(CapellCore::cacheExists($unrelatedCacheKey))->toBeTrue();
});
