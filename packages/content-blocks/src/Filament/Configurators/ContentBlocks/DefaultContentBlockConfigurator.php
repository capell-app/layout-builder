<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Configurators\ContentBlocks;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PageSelect;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\ContentBlocks\Enums\ConfiguratorTypeEnum;
use Capell\ContentBlocks\Enums\SchemaExtenderEnum;
use Capell\ContentBlocks\Filament\Components\Forms\Content\DetailsSchema;
use Capell\ContentBlocks\Filament\Components\Forms\Content\SettingsSchema;
use Capell\ContentBlocks\Filament\Components\Forms\Content\TranslationsRepeater;
use Capell\ContentBlocks\Filament\Components\Forms\CustomColorInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DefaultContentBlockConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::ContentBlock;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::ContentBlock->value);
    }

    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'editOption', 'replicate' => $this->getOptionFormSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

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
            ...DetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator)
                ->hiddenLabel()
                ->contained(),
            ...SettingsSchema::make($configurator),
            MediaLibraryFileUpload::make('image'),
            PublishSchema::make($configurator),
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

    protected function settingsTab(Schema $configurator): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::generic.settings'))
            ->statePath('meta')
            ->columns()
            ->schema($this->getMetaSchema());
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
}
