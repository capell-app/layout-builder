<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Schemas\Types;

use Capell\Admin\Contracts\Schemas\PageSchemaExtenderResolverInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\PagePublishSection;
use Capell\Admin\Filament\Components\Forms\Page\PageSettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\PageSiteSelect;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Resources\Pages\RelationManagers\UrlsRelationManager;
use Capell\Admin\Filament\Resources\Pages\Schemas\Types\DefaultPageSchema;
use Capell\Blog\Filament\Components\Forms\Article\ArticleTagsInput;
use Capell\Blog\Filament\Components\Forms\Article\Tab\ArticleSettingsTab;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Closure;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class ArticlePageSchema extends DefaultPageSchema
{
    protected bool $hasCreatePageSchema = false;

    public static function relationManagers(Model $record): array
    {
        return [
            UrlsRelationManager::class,
        ];
    }

    protected static function modifyParentQueryUsing(Schema $schema): Closure
    {
        return function (Builder $query) use ($schema) {
            /** @var class-string<Site> $model */
            $model = CapellCore::getModel(ModelEnum::Site);

            $site = $model::query()->find($schema->getRawState()['site_id']);

            $blogPage = $site ? BlogLoader::getBlogPage($site) : null;

            return $query->adminResource(
                $schema->getLivewire()->getResource()::getResourceName(),
            )
                ->when(
                    $blogPage,
                    fn (Builder $query) => $query->orWhere('id', $blogPage->id),
                );
        };
    }

    #[Override]
    protected function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    $this->getTranslationFormSchema($schema),
                ])
                ->sidebarSchema(
                    PageSettingsSchema::make(
                        $schema,
                        components: [
                            ArticleTagsInput::make('tags'),
                        ],
                        pageGroup: $schema->getLivewire()->getResource()::getResourceName(),
                        modifyParentQueryUsing: static::modifyParentQueryUsing($schema),
                        withParent: false,
                        withType: false,
                    ),
                    contained: true,
                ),
            Tabs::make()
                ->columnSpanFull()
                ->tabs($this->getTabs($schema)),
        ];
    }

    protected function getTabs(Schema $schema): array
    {
        return app(PageSchemaExtenderResolverInterface::class)->resolveTabs($schema, [
            ArticleSettingsTab::make($schema),
        ]);
    }

    #[Override]
    protected function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            $this->getTranslationFormSchema($schema),
            Section::make(__('capell-admin::generic.settings'))
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->schema([
                    ...PageSettingsSchema::make(
                        $schema,
                        components: [
                            ArticleTagsInput::make('tags'),
                            MediaLibraryFileUpload::make('image'),
                        ],
                        pageGroup: $schema->getLivewire()->getResource()::getResourceName(),
                        modifyParentQueryUsing: static::modifyParentQueryUsing($schema),
                        withParent: false,
                        withType: false,
                    ),
                    PagePublishSection::make(),
                ]),
        ];
    }

    #[Override]
    protected function getCreateExtraFor(Schema $schema): array
    {
        return [
            PageSiteSelect::make(),
            LayoutSelect::make('layout_id')
                ->reactive(),
            ArticleTagsInput::make('tags'),
            PublishSchema::make($schema),
        ];
    }
}
