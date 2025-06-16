<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\ArticleResource\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\PageResource\Pages\ListPages;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Filament\Resources\ArticleResource;

class ListArticles extends ListPages
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(BlogResourceEnum::Article->value);
    }
}
