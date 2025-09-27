<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
            MediaLibraryFileUpload::make('image')
                ->imageDefaults(),
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
                ->schema(ContentSettingsSchema::make($schema)),
            ContentTranslationsRepeater::make($schema),
        ];
    }

    protected function getCreateOptionFormSchema(Schema $schema): array
    {
        return [
            ...ContentSettingsSchema::make($schema),
            ContentTranslationsRepeater::make($schema),
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
                    ContentTranslationsRepeater::make($schema),
                    Section::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema($this->getMetaSchema()),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema([
                            ...ContentDetailsSchema::make($schema),
                            ...ContentSettingsSchema::make($schema),
                        ]),
                    PublishSection::make(),
                ]),
        ];

    }

    protected function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            ContentTranslationsRepeater::make($schema, hasTitle: false),
            Grid::make()
                ->statePath('meta')
                ->schema($this->getMetaSchema()),
            Section::make(__('capell-admin::generic.settings'))
                ->collapsed()
                ->compact()
                ->icon('heroicon-o-cog-6-tooth')
                ->columns()
                ->schema([
                    ...ContentDetailsSchema::make($schema),
                    ...ContentSettingsSchema::make($schema),
                    PublishSection::make(),
                ]),
        ];
    }
}
