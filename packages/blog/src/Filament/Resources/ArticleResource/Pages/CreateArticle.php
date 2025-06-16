<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\ArticleResource\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\PageResource\Pages\CreatePage;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Filament\Resources\ArticleResource;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Models\Widget;

class CreateArticle extends CreatePage
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(BlogResourceEnum::Article->value);
    }

    protected function afterFill(): void
    {
        $this->data['layout_id'] = $this->getArticleLayoutId();

        $this->data['type_id'] = CapellCore::getModel(ModelEnum::Type)::pageType()->where('key', 'article')->value('id');
    }

    private function getArticleLayoutId(): ?int
    {
        /** @var class-string<Widget> $model */
        $model = CapellCore::getModel(LayoutModelEnum::Widget->name);

        return $model::where('key', 'article')->value('id');
    }
}
