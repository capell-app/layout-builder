<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Pages;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Illuminate\Contracts\Support\Htmlable;

class ListArticles extends ListPages
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resourceIfRegistered(AdminResourceEnum::Page, strtolower(ResourceEnum::Article->name))
            ?? ArticleResource::class;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-blog::generic.articles_info');
    }
}
