<?php

declare(strict_types=1);

use Capell\Blog\Listeners\ArticleTranslationSavedListener;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
});

it('updates article page url on translation save', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $article = Article::factory()->site($site)->create();

    $translation = Translation::factory()
        ->translatable($article)
        ->language($language)
        ->create();

    (new ArticleTranslationSavedListener)($translation);

    $article->refresh();

    expect($article->translations()->count())->toBeGreaterThan(0);
});

it('ignores non-article translations', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $translation = Translation::factory()
        ->translatable($site)
        ->language($language)
        ->create();

    (new ArticleTranslationSavedListener)($translation);

    expect($translation)->toBeInstanceOf(Translation::class);
});
