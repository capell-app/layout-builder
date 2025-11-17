<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Pages;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;

class ListArticles extends ListPages
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(AdminResourceEnum::Page, strtolower(ResourceEnum::Article->name));
    }
}
