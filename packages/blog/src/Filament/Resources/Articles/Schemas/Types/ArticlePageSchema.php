<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\PagePublishSection;
use Capell\Admin\Filament\Components\Forms\Page\PageSettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\PageSiteSelect;
use Capell\Admin\Filament\Components\Forms\Page\ParentPageSelect;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Resources\Pages\Schemas\Types\DefaultPageSchema;
use Capell\Blog\Filament\Components\Forms\Page\PageTagsInput;
use Capell\Blog\Services\Loader\BlogLoader;
use Closure;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ArticlePageSchema extends DefaultPageSchema
{
    protected bool $hasCreatePageSchema = false;

    protected static function modifyParentQueryUsing(Schema $schema): Closure
    {
        return function (Builder $query) use ($schema) {
            $site = $schema->getLivewire()->getSite($schema);

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
                            PageTagsInput::make('tags'),
                        ],
                        pageGroup: $schema->getLivewire()->getResource()::getResourceName(),
                        modifyParentQueryUsing: static::modifyParentQueryUsing($schema),
                        withType: false,
                    ),
                    contained: true,
                ),
            Tabs::make()
                ->columnSpanFull()
                ->tabs($this->getTabs($schema)),
        ];
    }

    #[Override]
    protected function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            $this->getTranslationFormSchema($schema),
            Section::make(__('capell-admin::generic.settings'))
                ->compact()
                ->schema([
                    ...PageSettingsSchema::make(
                        $schema,
                        components: [
                            PageTagsInput::make('tags'),
                            MediaLibraryFileUpload::make('image'),
                        ],
                        pageGroup: $schema->getLivewire()->getResource()::getResourceName(),
                        modifyParentQueryUsing: static::modifyParentQueryUsing($schema),
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
            $this->getParentPageSelect($schema),
            LayoutSelect::make('layout_id')
                ->reactive()
                ->withEditLink(),
            PublishSchema::make($schema),
        ];
    }

    #[Override]
    protected function getParentPageSelect(Schema $schema): ParentPageSelect
    {
        return ParentPageSelect::make('parent_id')
            ->label(__('capell-layout::form.parent_page'))
            ->setupRelation('parent', $schema)
            ->pageGroup(static::modifyParentQueryUsing($schema))
            ->reactive();
    }
}
