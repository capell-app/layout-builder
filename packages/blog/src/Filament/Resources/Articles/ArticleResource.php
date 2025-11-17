<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles;

use BackedEnum;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Filament\Resources\Articles\Pages\CreateArticle;
use Capell\Blog\Filament\Resources\Articles\Pages\EditArticle;
use Capell\Blog\Filament\Resources\Articles\Pages\ListArticles;
use Capell\Blog\Filament\Resources\Articles\Tables\ArticlePagesTable;
use Capell\Blog\Models\Article;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Actions\GetNameFromTranslationsAction;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ArticleResource extends PageResource
{
    protected static string $adminResourceName = ResourceEnum::Article->name;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'article';

    /** @var class-string<TableConfigurator> */
    protected static string $tableConfigurator = ArticlePagesTable::class;

    /**
     * @return class-string<Article>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(ModelEnum::Article->name);
    }

    public static function getResourceType(): string
    {
        return AdminResourceEnum::Page->name;
    }

    public static function getLabel(): string
    {
        return __('capell-blog::generic.article');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
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
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-blog::generic.articles');
    }

    #[Override]
    public static function mutateFormDataBeforeCreate(array &$data, array $formData = []): void
    {
        $data['layout_id'] = GetArticleLayoutAction::run()?->id;

        /* @var class-string<\Capell\Core\Models\Type> $model */
        $model = CapellCore::getModel(CoreModelEnum::Type);

        $data['type_id'] = $model::query()
            ->pageType()
            ->where('group', BlogTypeGroupEnum::Article)
            ->value('id');

        $siteId = $data['site_id'] ?? null;

        /* @var class-string<\Capell\Core\Models\Site> $model */
        $model = CapellCore::getModel(CoreModelEnum::Site);

        $site = $model::find($siteId) ?: $model::default()->first();

        if (! $site) {
            return;
        }

        if (empty($data['site_id'])) {
            $data['site_id'] = $site->id;
        }

        if (empty($data['parent_id'])) {
            $data['parent_id'] = BlogLoader::getBlogPage($site)?->id;
        }

        if (empty($data['name']) && ! empty($formData['translations'])) {
            $data['name'] = GetNameFromTranslationsAction::run(collect($formData['translations']), $site);
        }
    }

    #[Override]
    public static function applyTypeAdminResourceConstraint(BuilderContract $query, ?bool $hideSystemPages = false): void
    {
        $query->where('group', BlogTypeGroupEnum::Article);
    }
}
