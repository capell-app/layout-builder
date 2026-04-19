<?php

declare(strict_types=1);

use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Blog\Support\Creator\ArticleCreator;
use Capell\Core\Models\Layout;

it('returns article layout by key', function (): void {
    $articleCreator = resolve(ArticleCreator::class);
    $articleCreator->createArticlePageType();

    $layout = GetArticleLayoutAction::run();

    expect($layout)->toBeInstanceOf(Layout::class)
        ->key->toBe(BlogLayoutEnum::Article->value);
});

it('returns null when article layout does not exist', function (): void {
    $layout = GetArticleLayoutAction::run();

    expect($layout)->toBeNull();
});
