<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Sections;

use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PageSelect;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\LayoutBuilder\Filament\Components\Forms\ActionsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\DetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\RelatedRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\TranslationsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\CustomColorInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HeroSectionConfigurator extends DefaultSectionConfigurator
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

    protected function getOptionFormSchema(Schema $configurator): array
    {
        return [
            ...($configurator->getOperation() === 'create' ? DetailsSchema::make($configurator) : []),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->translationsTab($configurator),
                    $this->mediaTab($configurator)
                        ->key('media')
                        ->statePath('meta'),
                    $this->relatedTab($configurator)
                        ->key('related')
                        ->statePath('meta'),
                    $this->actionsTab()
                        ->key('actions')
                        ->statePath('meta'),
                    $this->settingsTab($configurator, components: [
                        ...($configurator->getOperation() !== 'create' ? DetailsSchema::make($configurator) : []),
                    ]),
                ]),
            PublishSection::make(),
        ];
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            Section::make()
                ->hiddenOn('edit')
                ->columnSpanFull()
                ->columns()
                ->schema(DetailsSchema::make($configurator))
                ->contained(fn (string $operation): bool => $operation === 'create'),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->tabs([
                            $this->translationsTab($configurator),
                            $this->mediaTab($configurator)
                                ->statePath('meta'),
                            $this->relatedTab($configurator)
                                ->statePath('meta'),
                            $this->actionsTab()
                                ->statePath('meta'),
                            $this->settingsTab($configurator),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['default' => 1, '@lg' => 2])
                        ->schema([
                            ...($configurator->getOperation() !== 'create' ? DetailsSchema::make($configurator) : []),
                            ...SettingsSchema::make($configurator),
                        ]),
                    PublishSection::make(),
                ]),
        ];
    }

    protected function settingsTab(Schema $configurator, array $components = []): Tab
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

    protected function translationsTab(Schema $configurator): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->icon(Heroicon::Language)
            ->schema([
                TranslationsRepeater::make($configurator)
                    ->hiddenLabel(),
            ]);
    }

    protected function actionsTab(): Tab
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

    protected function mediaTab(Schema $configurator): Tab
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
                self::getAssetsComponent($configurator),
            ]);
    }

    protected function relatedTab(Schema $configurator): Tab
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
                RelatedRepeater::make($configurator),
            ]);
    }

    protected function getAssetsComponent(Schema $configurator): AssetsRepeater
    {
        return AssetsRepeater::make('assets')
            ->compactRepeater()
            ->hiddenLabel()
            ->hint(__('capell-layout-builder::generic.widget_assets_repeater_hint'));
    }
}
