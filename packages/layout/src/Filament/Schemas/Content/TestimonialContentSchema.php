<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Content;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentPublishSection;
use Capell\Layout\Filament\Components\Forms\Content\ContentSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialContentSchema extends DefaultContentSchema
{
    public static function getMetaSchema(): array
    {
        return [
            CuratorPicker::make('image_id')
                ->label(__('capell-admin::form.image')),
            Group::make()
                ->schema([
                    TextInput::make('company')
                        ->label(__('capell-layout::form.company')),
                    TextInput::make('position')
                        ->label(__('capell-layout::form.position')),
                ]),
        ];
    }

    public static function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'replicate' => self::getCreateOptionFormSchema($schema),
            'create' => self::getCreateFormSchema($schema),
            'editOption' => self::getEditOptionFormSchema($schema),
            default => self::getEditFormSchema($schema),
        };
    }

    protected static function getCreateFormSchema(Schema $schema): array
    {
        return [
            Section::make()
                ->columns()
                ->schema(ContentSettingsSchema::make($schema)),
            ContentTranslationsRepeater::make($schema),
        ];
    }

    protected static function getCreateOptionFormSchema(Schema $schema): array
    {
        return [
            ...ContentSettingsSchema::make($schema),
            ContentTranslationsRepeater::make($schema),
            Grid::make()
                ->statePath('meta')
                ->mutateDehydratedStateUsing(function (array $state): array {
                    if (isset($state['image_id'])) {
                        $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                    }

                    return $state;
                })
                ->schema(self::getMetaSchema()),
        ];
    }

    protected static function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    ContentTranslationsRepeater::make($schema),
                    Section::make()
                        ->columns()
                        ->statePath('meta')
                        ->mutateDehydratedStateUsing(function (array $state): array {
                            if (isset($state['image_id'])) {
                                $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                            }

                            return $state;
                        })
                        ->schema(self::getMetaSchema()),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema([
                            ...ContentDetailsSchema::make(),
                            ...ContentSettingsSchema::make($schema),
                        ]),
                    ContentPublishSection::make(),
                ]),
        ];

    }

    protected static function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            ContentTranslationsRepeater::make($schema, hasTitle: false),
            Grid::make()
                ->statePath('meta')
                ->mutateDehydratedStateUsing(function (array $state): array {
                    if (isset($state['image_id'])) {
                        $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                    }

                    return $state;
                })
                ->schema(self::getMetaSchema()),
            Section::make(__('capell-admin::generic.settings'))
                ->collapsed()
                ->compact()
                ->icon('heroicon-o-cog-6-tooth')
                ->columns()
                ->schema([
                    ...ContentDetailsSchema::make(),
                    ...ContentSettingsSchema::make($schema),
                    ContentPublishSection::make(),
                ]),
        ];
    }
}
