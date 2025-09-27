<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Pages;

use Capell\Admin\Actions\BuildDefaultTranslationsAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\Pages\CreatePage;
use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;

class CreateArticle extends CreatePage
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Page, BlogResourceEnum::Article->value);
    }

    protected function afterFill(): void
    {
        $this->data['site_id'] ??= request('site_id', Site::getDefault()?->id);

        $this->data['layout_id'] = GetArticleLayoutAction::run()?->id;

        $this->data['type_id'] = CapellCore::getModel(ModelEnum::Type)::pageType()->where('key', 'article')->value('id');

        if (empty($this->data['translations']) && ! empty($this->data['site_id'])) {
            $this->data['translations'] = BuildDefaultTranslationsAction::run($this->data['site_id']);
        }
    }
}
