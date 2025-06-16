<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\ArticleResource\Pages;

use Capell\Admin\Exceptions\InvalidPageTypeException;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\PageResource\Pages\EditPage;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Filament\Resources\ArticleResource;
use Capell\Core\Models;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Override;

class EditArticle extends EditPage
{
    /** @return class-string<ArticleResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(BlogResourceEnum::Article->value);
    }

    /**
     * @param  Models\Page  $record
     */
    #[Override]
    protected function validateResource(Model $record): bool
    {
        if (! $record->type) {
            throw new Exception('Page type not found.');
        }

        $pageGroup = $record->type->group ?? '';

        if ($pageGroup !== 'article') {
            throw new InvalidPageTypeException(sprintf("Invalid page type group '%s' for article resource.", $pageGroup));
        }

        return true;
    }
}
