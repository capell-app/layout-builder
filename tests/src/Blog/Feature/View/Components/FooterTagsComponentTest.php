<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\View\Components\Footer\Tags;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\View\View;

uses(TestingFrontend::class);

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
    $blogCreator->createTagPageType();
});

it('renders tags footer component with tags', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $article = Article::factory()->site($site)->create();

    $tag = Tag::factory()->site($site)->create();
    $article->tags()->attach($tag);

    $component = new Tags;

    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class);
});

it('renders when page has no tags', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $component = new Tags;

    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class);
});
