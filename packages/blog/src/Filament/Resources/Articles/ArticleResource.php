<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles;

use BackedEnum;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Blog\Actions\GetArticleLayoutAction;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Articles\Pages\CreateArticle;
use Capell\Blog\Filament\Resources\Articles\Pages\EditArticle;
use Capell\Blog\Filament\Resources\Articles\Pages\ListArticles;
use Capell\Blog\Filament\Resources\Articles\Schemas\ArticleForm;
use Capell\Blog\Filament\Resources\Articles\Tables\ArticlePagesTable;
use Capell\Blog\Models\Article;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Actions\GetNameFromTranslationsAction;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;

class ArticleResource extends PageResource
{
    protected static string $adminResourceName = ResourceEnum::Article->name;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'article';

    protected static string $tableConfigurator = ArticlePagesTable::class;

    protected static string $formConfigurator = ArticleForm::class;

    /**
     * @return class-string<Article>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(ModelEnum::Article->name);
    }

    public static function getResourceType(): string
    {
        return 'Pages';
    }

    public static function getBasePath(Site $site, Language $language): string
    {
        return BlogLoader::getBlogPageUrl($site, $language, fullUrl: false) . '/';
    }

    public static function getLabel(): string
    {
        return __('capell-blog::generic.article');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedNewspaper;
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::Newspaper;
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-blog::generic.articles'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(BlogServiceProvider::$packageName)->isInstalled();
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

        $site = $model::query()->find($siteId) ?? $model::default()->first();

        if ($site === null) {
            return;
        }

        if (! isset($data['site_id']) || blank($data['site_id'])) {
            $data['site_id'] = $site->id;
        }

        if ((! isset($data['name']) || blank($data['name'])) && isset($formData['translations'])) {
            $data['name'] = GetNameFromTranslationsAction::run(collect($formData['translations']), $site);
        }
    }

    public static function applyTypeAdminResourceConstraint(BuilderContract $query, ?bool $hideSystemPages = false): void
    {
        $query->where('group', BlogTypeGroupEnum::Article);
    }
}
