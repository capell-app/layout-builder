<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Content;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentPublishSection;
use Capell\Layout\Filament\Components\Forms\Content\ContentSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\CustomColorInput;
use Capell\Layout\Filament\Schemas\AbstractContentSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DefaultContentSchema extends AbstractContentSchema
{
    public static function getMetaSchema(): array
    {
        return [
            IconPicker::make('icon')
                ->label(__('capell-admin::form.icon')),
            CuratorPicker::make('image_id')
                ->label(__('capell-admin::form.image')),
            CustomColorInput::make(
                name: 'color',
                label: __('capell-admin::form.color'),
            ),
            Group::make()
                ->schema([
                    PageSelect::make('page_id')
                        ->label(__('capell-admin::form.related_page'))
                        ->reactive(),
                    CallToActionText::make('link_text')
                        ->hidden(fn (Get $get): bool => $get('page_id') === null),
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
                ->columnSpanFull()
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
                        ->gridContainer()
                        ->columns(['default' => 1, '@lg' => 2])
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
