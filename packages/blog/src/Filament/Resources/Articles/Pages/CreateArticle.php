<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Pages;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Filament\Resources\Pages\Pages\CreatePage;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Core\Models\Type;

class CreateArticle extends CreatePage
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resourceIfRegistered(AdminResourceEnum::Page, strtolower(ResourceEnum::Article->name))
            ?? ArticleResource::class;
    }

    protected function beforeFill(): void
    {
        parent::beforeFill();

        $this->data['layout_id'] = GetArticleLayoutAction::run()?->id;

        /** @var class-string<Type> $model */
        $model = Type::class;

        $this->data['type_id'] = $model::query()
            ->pageType()
            ->where('key', BlogPageTypeEnum::Article->value)
            ->value('id');
    }
}
