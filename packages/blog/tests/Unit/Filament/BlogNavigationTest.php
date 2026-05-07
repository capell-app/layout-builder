<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Tags\Filament\Resources\Tags\TagResource;

it('keeps articles primary and nests tag management below it when blog is installed', function (): void {
    expect(ArticleResource::getNavigationItems()[0]->getSort())->toBe(2)
        ->and(ArticleResource::getNavigationParentItem())->toBeNull()
        ->and(TagResource::getNavigationParentItem())->toBe((string) __('capell-blog::generic.articles'));
});
