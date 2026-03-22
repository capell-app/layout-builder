<?php

declare(strict_types=1);

namespace Capell\Hero\Filament\Resources\Contents\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PageSelect;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Layout\Filament\Components\Forms\ActionsRepeater;
use Capell\Layout\Filament\Components\Forms\AssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\Content\RelatedRepeater;
use Capell\Layout\Filament\Components\Forms\CustomColorInput;
use Capell\Layout\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HeroContentSchema extends DefaultContentSchema
{
    protected function getMetaSchema(): array
    {
        return [
            IconPicker::make('icon')
                ->label(__('capell-admin::form.icon')),
            MediaLibraryFileUpload::make('image'),
            CustomColorInput::make(
                name: 'color',
                label: __('capell-admin::form.color'),
            ),
            Group::make()
                ->schema([
                    PageSelect::make('page_id')
                        ->label(__('capell-admin::form.related_page')),
                    CallToActionText::make('link_text')
                        ->hiddenJs(<<<'JS'
                             ! $get('page_id')
                        JS),
                ]),
        ];
    }

    protected function getOptionFormSchema(Schema $schema): array
    {
        return [
            ...($schema->getOperation() === 'create' ? ContentDetailsSchema::make($schema) : []),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->getTranslationsTab($schema),
                    $this->getMediaTab($schema)
                        ->key('media')
                        ->statePath('meta'),
                    $this->getRelatedTab($schema)
                        ->key('related')
                        ->statePath('meta'),
                    $this->getActionsTab()
                        ->key('actions')
                        ->statePath('meta'),
                    $this->getSettingsTab($schema, components: [
                        ...($schema->getOperation() !== 'create' ? ContentDetailsSchema::make($schema) : []),
                    ]),
                ]),
            PublishSection::make(),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            Section::make()
                ->hiddenOn('edit')
                ->columnSpanFull()
                ->columns()
                ->schema(ContentDetailsSchema::make($schema))
                ->contained(fn (string $operation): bool => $operation === 'create'),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->tabs([
                            $this->getTranslationsTab($schema),
                            $this->getMediaTab($schema)
                                ->statePath('meta'),
                            $this->getRelatedTab($schema)
                                ->statePath('meta'),
                            $this->getActionsTab()
                                ->statePath('meta'),
                            $this->getSettingsTab($schema),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['default' => 1, '@lg' => 2])
                        ->schema([
                            ...($schema->getOperation() !== 'create' ? ContentDetailsSchema::make($schema) : []),
                            ...ContentSettingsSchema::make($schema),
                        ]),
                    PublishSection::make(),
                ]),
        ];
    }

    protected function getSettingsTab(Schema $schema, array $components = []): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::generic.settings'))
            ->statePath('meta')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->columns()
            ->schema([
                ...$this->getMetaSchema(),
                ...$components,
            ]);
    }

    protected function getTranslationsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->icon(Heroicon::Language)
            ->schema([
                ContentTranslationsRepeater::make($schema)
                    ->hiddenLabel(),
            ]);
    }

    protected function getActionsTab(): Tab
    {
        return Tab::make('actions')
            ->label(__('capell-admin::generic.links'))
            ->badge(function (Get $get): ?int {
                if (! is_array($get('actions'))) {
                    return null;
                }

                $count = count($get('actions'));

                if ($count === 0) {
                    return null;
                }

                return $count;
            })
            ->icon('heroicon-o-link')
            ->schema([
                ActionsRepeater::make('actions')
                    ->hiddenLabel(),
            ]);
    }

    protected function getMediaTab(Schema $schema): Tab
    {

        return Tab::make('media')
            ->label(__('capell-admin::generic.media'))
            ->badge(function (Get $get): ?int {
                if (! is_array($get('assets'))) {
                    return null;
                }

                $count = count($get('assets'));

                if ($count === 0) {
                    return null;
                }

                return $count;
            })
            ->icon('heroicon-o-photo')
            ->schema([
                self::getAssetsComponent($schema),
            ]);
    }

    protected function getRelatedTab(Schema $schema): Tab
    {
        return Tab::make('related')
            ->label(__('capell-admin::generic.related'))
            ->badge(function (Get $get): ?int {
                if (! is_array($get('related'))) {
                    return null;
                }

                $count = count($get('related'));

                if ($count === 0) {
                    return null;
                }

                return $count;
            })
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->schema([
                RelatedRepeater::make($schema),
            ]);
    }

    protected function getAssetsComponent(Schema $schema): AssetsRepeater
    {
        return AssetsRepeater::make('assets')
            ->compactRepeater()
            ->hiddenLabel()
            ->hint(__('capell-layout::generic.widget_assets_repeater_hint'));
    }
}
