<?php

declare(strict_types=1);

namespace Capell\Hero\Filament\Resources\Contents\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PageSelect;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Mosaic\Filament\Components\Forms\ActionsRepeater;
use Capell\Mosaic\Filament\Components\Forms\AssetsRepeater;
use Capell\Mosaic\Filament\Components\Forms\Content\DetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Content\RelatedRepeater;
use Capell\Mosaic\Filament\Components\Forms\Content\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Content\TranslationsRepeater;
use Capell\Mosaic\Filament\Components\Forms\CustomColorInput;
use Capell\Mosaic\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
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
            ...($schema->getOperation() === 'create' ? DetailsSchema::make($schema) : []),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->translationsTab($schema),
                    $this->mediaTab($schema)
                        ->key('media')
                        ->statePath('meta'),
                    $this->relatedTab($schema)
                        ->key('related')
                        ->statePath('meta'),
                    $this->actionsTab()
                        ->key('actions')
                        ->statePath('meta'),
                    $this->settingsTab($schema, components: [
                        ...($schema->getOperation() !== 'create' ? DetailsSchema::make($schema) : []),
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
                ->schema(DetailsSchema::make($schema))
                ->contained(fn (string $operation): bool => $operation === 'create'),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->tabs([
                            $this->translationsTab($schema),
                            $this->mediaTab($schema)
                                ->statePath('meta'),
                            $this->relatedTab($schema)
                                ->statePath('meta'),
                            $this->actionsTab()
                                ->statePath('meta'),
                            $this->settingsTab($schema),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['default' => 1, '@lg' => 2])
                        ->schema([
                            ...($schema->getOperation() !== 'create' ? DetailsSchema::make($schema) : []),
                            ...SettingsSchema::make($schema),
                        ]),
                    PublishSection::make(),
                ]),
        ];
    }

    protected function settingsTab(Schema $schema, array $components = []): Tab
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

    protected function translationsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->icon(Heroicon::Language)
            ->schema([
                TranslationsRepeater::make($schema)
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

    protected function mediaTab(Schema $schema): Tab
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

    protected function relatedTab(Schema $schema): Tab
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
            ->hint(__('capell-mosaic::generic.widget_assets_repeater_hint'));
    }
}
