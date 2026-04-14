<?php

declare(strict_types=1);

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;

it('creates all required blog pages and links them for a site', function (): void {
    $site = Site::factory()->create();

    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createArticlePageType();
    $blogCreator->createArchivePageType();
    $blogCreator->createBlogPageType();
    $blogCreator->createTagPageType();

    CreateBlogPagesAction::run($site);

    $blogPage = $site->pages()
        ->with('layout')
        ->whereHas(
            'type',
            fn (Builder $builder) => $builder->where('key', BlogPageTypeEnum::Blog->value),
        )
        ->first();

    $archivesPage = $site->pages()
        ->with('layout')
        ->whereHas(
            'type',
            fn (Builder $builder) => $builder->where('key', BlogPageTypeEnum::Archive->value),
        )
        ->first();

    $tagsPage = $site->pages()
        ->with('layout')
        ->whereHas(
            'type',
            fn (Builder $builder) => $builder->where('key', BlogPageTypeEnum::Tag->value),
        )
        ->first();

    expect($blogPage)->toBeInstanceOf(Page::class)
        ->type->name->toBe('Blog')
        ->layout->name->toBe('Blog Posts')
        ->and($archivesPage)->toBeInstanceOf(Page::class)
        ->type->name->toBe('Archive Page')
        ->layout->name->toBe('Results')
        ->and($tagsPage)->toBeInstanceOf(Page::class)
        ->type->name->toBe('Tag Page')
        ->layout->name->toBe('Results');
});
