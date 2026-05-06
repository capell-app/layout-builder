<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Sections;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\DetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Content\TranslationsRepeater;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TestimonialSectionConfigurator extends DefaultSectionConfigurator
{
    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionFormSchema($configurator),
            'editOption' => $this->getEditOptionFormSchema($configurator),
            'edit' => $this->getEditFormSchema($configurator),
            'create' => $this->getCreateFormSchema($configurator),
        };
    }

    protected function getMetaSchema(): array
    {
        return [
            MediaLibraryFileUpload::make('image'),
            Group::make()
                ->schema([
                    TextInput::make('company')
                        ->label(__('capell-layout-builder::form.company')),
                    TextInput::make('position')
                        ->label(__('capell-layout-builder::form.position')),
                ]),
        ];
    }

    protected function getCreateFormSchema(Schema $configurator): array
    {
        return [
            Section::make()
                ->columns()
                ->schema(SettingsSchema::make($configurator)),
            TranslationsRepeater::make($configurator),
        ];
    }

    protected function getCreateOptionFormSchema(Schema $configurator): array
    {
        return [
            ...SettingsSchema::make($configurator),
            TranslationsRepeater::make($configurator),
            Grid::make()
                ->statePath('meta')
                ->schema($this->getMetaSchema()),
        ];
    }

    protected function getEditFormSchema(Schema $configurator): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($configurator),
                    Section::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema($this->getMetaSchema()),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema([
                            ...DetailsSchema::make($configurator),
                            ...SettingsSchema::make($configurator),
                        ]),
                    PublishSection::make(),
                ]),
        ];

    }

    protected function getEditOptionFormSchema(Schema $configurator): array
    {
        return [
            TranslationsRepeater::make($configurator),
            Grid::make()
                ->statePath('meta')
                ->columnSpanFull()
                ->schema($this->getMetaSchema()),
            Section::make(__('capell-admin::generic.settings'))
                ->collapsed()
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->columns()
                ->columnSpanFull()
                ->schema([
                    ...DetailsSchema::make($configurator),
                    ...SettingsSchema::make($configurator),
                    PublishSection::make(),
                ]),
        ];
    }
}
