<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Collections\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Layout\Filament\Components\Forms\Content\DetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\SettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\TranslationsRepeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TestimonialContentSchema extends DefaultContentSchema
{
    public function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionFormSchema($schema),
            'editOption' => $this->getEditOptionFormSchema($schema),
            'edit' => $this->getEditFormSchema($schema),
            'create' => $this->getCreateFormSchema($schema),
        };
    }

    protected function getMetaSchema(): array
    {
        return [
            MediaLibraryFileUpload::make('image'),
            Group::make()
                ->schema([
                    TextInput::make('company')
                        ->label(__('capell-layout::form.company')),
                    TextInput::make('position')
                        ->label(__('capell-layout::form.position')),
                ]),
        ];
    }

    protected function getCreateFormSchema(Schema $schema): array
    {
        return [
            Section::make()
                ->columns()
                ->schema(SettingsSchema::make($schema)),
            TranslationsRepeater::make($schema),
        ];
    }

    protected function getCreateOptionFormSchema(Schema $schema): array
    {
        return [
            ...SettingsSchema::make($schema),
            TranslationsRepeater::make($schema),
            Grid::make()
                ->statePath('meta')
                ->schema($this->getMetaSchema()),
        ];
    }

    protected function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($schema),
                    Section::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema($this->getMetaSchema()),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema([
                            ...DetailsSchema::make($schema),
                            ...SettingsSchema::make($schema),
                        ]),
                    PublishSection::make(),
                ]),
        ];

    }

    protected function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            TranslationsRepeater::make($schema),
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
                    ...DetailsSchema::make($schema),
                    ...SettingsSchema::make($schema),
                    PublishSection::make(),
                ]),
        ];
    }
}
