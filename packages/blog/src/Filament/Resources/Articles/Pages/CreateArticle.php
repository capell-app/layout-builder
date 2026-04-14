<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Pages;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\Pages\CreatePage;
use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;

class CreateArticle extends CreatePage
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(AdminResourceEnum::Page, strtolower(ResourceEnum::Article->name));
    }

    protected function beforeFill(): void
    {
        parent::beforeFill();

        $this->data['layout_id'] = GetArticleLayoutAction::run()?->id;

        /** @var class-string<Type> $model */
        $model = CapellCore::getModel(ModelEnum::Type);

        $this->data['type_id'] = $model::query()
            ->pageType()
            ->where('key', BlogPageTypeEnum::Article->value)
            ->value('id');
    }
}
