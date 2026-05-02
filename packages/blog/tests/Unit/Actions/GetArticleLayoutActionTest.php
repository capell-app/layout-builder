<?php

declare(strict_types=1);

use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Creator\LayoutCreator;

it('retrieves article layout by key', function (): void {
    resolve(LayoutCreator::class)->create('article');

    $layout = GetArticleLayoutAction::run();

    expect($layout)->toBeInstanceOf(Layout::class);
});

it('returns null when article layout does not exist', function (): void {
    $layout = GetArticleLayoutAction::run();

    expect($layout)->toBeNull();
});
