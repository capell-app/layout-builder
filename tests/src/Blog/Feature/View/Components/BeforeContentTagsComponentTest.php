<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\View\Components\BeforeContentTags;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\View\View;

uses(TestingFrontend::class);

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
    $blogCreator->createTagPageType();
});

it('renders before content tags component with article tags', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $article = Article::factory()->site($site)->create();

    $tag = Tag::factory()->site($site)->create();
    $article->tags()->attach($tag);

    $component = new BeforeContentTags;

    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class);
});
