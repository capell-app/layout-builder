<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\View\Components\ArticleMeta;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\View\View;

uses(TestingFrontend::class);

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
    $blogCreator->createTagPageType();
});

it('renders article meta with tags', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $article = Article::factory()->site($site)->create();

    $tag = Tag::factory()->site($site)->create();
    $article->tags()->attach($tag);

    $component = new ArticleMeta;

    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class);
});

it('renders with author when provided', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $component = new ArticleMeta(withAuthor: true, author: $site);

    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class);
});

it('returns null when no tags and no author', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $component = new ArticleMeta(withAuthor: false);

    $view = $component->render();

    expect($view)->toBeNull();
});
