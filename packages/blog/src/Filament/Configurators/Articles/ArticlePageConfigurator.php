<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Configurators\Articles;

use Capell\Admin\Contracts\Schemas\PageSchemaExtenderResolverInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\SettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\SiteSelect;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Admin\Filament\Configurators\Pages\DefaultPageConfigurator;
use Capell\Admin\Filament\Resources\Pages\RelationManagers\UrlsRelationManager;
use Capell\Blog\Filament\Components\Forms\Article\Tab\SettingsTab;
use Capell\Blog\Filament\Components\Forms\Article\TagsInput;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Models\Site;
use Closure;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class ArticlePageConfigurator extends DefaultPageConfigurator
{
    protected bool $hasCreatePageSchema = false;

    public static function relationManagers(Model $record): array
    {
        return [
            UrlsRelationManager::class,
        ];
    }

    protected static function modifyParentQueryUsing(Schema $configurator): Closure
    {
        return function (Builder $query) use ($configurator) {
            /** @var class-string<Site> $model */
            $model = Site::class;

            $site = $model::query()->find($configurator->getRawState()['site_id']);

            $blogPage = $site ? BlogLoader::getBlogPage($site) : null;

            return $query->adminResource(
                $configurator->getLivewire()->getResource()::getResourceName(),
            )
                ->when(
                    $blogPage,
                    fn (Builder $query) => $query->orWhere('id', $blogPage->id),
                );
        };
    }

    #[Override]
    protected function getEditFormSchema(Schema $configurator): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    $this->getTranslationFormSchema($configurator),
                ])
                ->sidebarSchema(
                    SettingsSchema::make(
                        $configurator,
                        components: [
                            TagsInput::make('tags'),
                        ],
                        pageGroup: $configurator->getLivewire()->getResource()::getResourceName(),
                        modifyParentQueryUsing: static::modifyParentQueryUsing($configurator),
                        withParent: false,
                        withType: false,
                    ),
                    contained: true,
                ),
            Tabs::make()
                ->columnSpanFull()
                ->tabs($this->getTabs($configurator)),
        ];
    }

    protected function getTabs(Schema $configurator): array
    {
        return resolve(PageSchemaExtenderResolverInterface::class)->resolveTabs($configurator, [
            SettingsTab::make($configurator),
        ]);
    }

    #[Override]
    protected function getEditOptionFormSchema(Schema $configurator): array
    {
        return [
            $this->getTranslationFormSchema($configurator),
            Section::make(__('capell-admin::generic.settings'))
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->schema([
                    ...SettingsSchema::make(
                        $configurator,
                        components: [
                            TagsInput::make('tags'),
                            MediaLibraryFileUpload::make('image'),
                        ],
                        pageGroup: $configurator->getLivewire()->getResource()::getResourceName(),
                        modifyParentQueryUsing: static::modifyParentQueryUsing($configurator),
                        withParent: false,
                        withType: false,
                    ),
                    PublishSection::make(),
                ]),
        ];
    }

    #[Override]
    protected function getCreateExtraFor(Schema $configurator): array
    {
        return [
            SiteSelect::make(),
            LayoutSelect::make('layout_id')
                ->reactive(),
            TagsInput::make('tags'),
            PublishSchema::make($configurator),
        ];
    }
}
