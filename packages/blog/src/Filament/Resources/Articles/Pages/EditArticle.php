<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Pages;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class EditArticle extends EditPage
{
    use InteractsWithRecord { resolveRecord as baseResolveRecord; }

    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resourceIfRegistered(AdminResourceEnum::Page, strtolower(ResourceEnum::Article->name))
            ?? ArticleResource::class;
    }
}
