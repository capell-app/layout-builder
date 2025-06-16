<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources;

use Capell\Admin\Enums\SchemaEnum;
use Capell\Admin\Filament\Resources\PageResource;
use Capell\Blog\Enums\BlogModelEnum;
use Capell\Blog\Filament\Resources\ArticleResource\Pages;
use Capell\Blog\Models\Article;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Override;

class ArticleResource extends PageResource
{
    protected static string $adminResourceName = 'article';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'article';

    protected static bool $withParent = false;

    public static function getResourceType(): SchemaEnum
    {
        return SchemaEnum::Page;
    }

    /**
     * @return class-string<Article>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(BlogModelEnum::Article->value);
    }

    public static function getLabel(): string
    {
        return __('capell-blog::generic.article');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-blog::generic.articles'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-blog::generic.articles');
    }

    #[Override]
    public static function mutateFormDataBeforeCreate(array &$data = [], array $formData = []): void
    {
        /* @var class-string<\Capell\Core\Models\Layout> $model */
        $model = CapellCore::getModel(ModelEnum::Layout);

        $data['layout_id'] = $model::query()->where('key', 'article')->value('id');

        /* @var class-string<\Capell\Core\Models\Type> $model */
        $model = CapellCore::getModel(ModelEnum::Type);

        $data['type_id'] = $model::query()->pageType()->where('group', 'article')->value('id');

        if (empty($data['parent_uuid'])) {
            /* @var class-string<\Capell\Core\Models\Site> $model */
            $model = CapellCore::getModel(ModelEnum::Site);

            $data['parent_uuid'] = BlogLoader::getBlogPage($model::find($data['site_id']))?->uuid;
        }
    }

    #[Override]
    protected static function applyTypeAdminResourceConstraint(BuilderContract $query, bool $showSystem = false): void
    {
        $query->where('group', 'article');
    }
}
