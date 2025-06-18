<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources;

use Capell\Admin\Filament\Resources\PageResource;
use Capell\Blog\Enums\BlogModelEnum;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Filament\Resources\ArticleResource\Pages;
use Capell\Blog\Models\Article;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Override;

class ArticleResource extends PageResource
{
    protected static string $adminResourceName = BlogResourceEnum::Article->name;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'article';

    protected static bool $withParent = false;

    /**
     * @return class-string<Article>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(BlogModelEnum::Article->name);
    }

    public static function getResourceType(): string
    {
        return 'Page';
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

        $data['type_id'] = $model::query()
            ->pageType()
            ->where('group', BlogTypeGroupEnum::Article)
            ->value('id');

        if (empty($data['parent_uuid'])) {
            /* @var class-string<\Capell\Core\Models\Site> $model */
            $model = CapellCore::getModel(ModelEnum::Site);

            $data['parent_uuid'] = BlogLoader::getBlogPage($model::find($data['site_id']))?->uuid;
        }
    }

    #[Override]
    protected static function applyTypeAdminResourceConstraint(BuilderContract $query, bool $showSystem = false): void
    {
        $query->where('group', BlogTypeGroupEnum::Article);
    }
}
