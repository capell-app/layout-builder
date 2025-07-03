<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Content;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Admin\Filament\Schemas\AbstractSchema;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentPublishSection;
use Capell\Layout\Filament\Components\Forms\Content\ContentSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Filament\Forms;

class DefaultContentSchema extends AbstractSchema
{
    protected static string $schemaType = SchemaEnum::Content->value;

    public static function getMetaSchema(): array
    {
        return [
            IconPicker::make('icon')
                ->label(__('capell-admin::form.icon')),
            CuratorPicker::make('image_id')
                ->label(__('capell-admin::form.image')),
            Forms\Components\Group::make()
                ->schema([
                    PageSelect::make('page_uuid')
                        ->label(__('capell-admin::form.related_page'))
                        ->reactive(),
                    CallToActionText::make('link_text')
                        ->hidden(fn (Forms\Get $get): bool => $get('page_uuid') === null),
                ]),
        ];
    }

    public static function make(Forms\Form $form): array
    {
        return match ($form->getOperation()) {
            'createOption', 'replicate' => self::getCreateOptionFormSchema($form),
            'create' => self::getCreateFormSchema($form),
            'editOption' => self::getEditOptionFormSchema($form),
            default => self::getEditFormSchema($form),
        };
    }

    protected static function getCreateFormSchema(Forms\Form $form): array
    {
        return [
            Forms\Components\Section::make()
                ->columns()
                ->schema(ContentSettingsSchema::make($form)),
            ContentTranslationsRepeater::make($form),
        ];
    }

    protected static function getCreateOptionFormSchema(Forms\Form $form): array
    {
        return [
            ...ContentSettingsSchema::make($form),
            ContentTranslationsRepeater::make($form),
            Forms\Components\Grid::make()
                ->statePath('meta')
                ->schema(self::getMetaSchema()),
        ];
    }

    protected static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    ContentTranslationsRepeater::make($form),
                    Forms\Components\Section::make()
                        ->columns()
                        ->statePath('meta')
                        ->schema(self::getMetaSchema()),
                ])
                ->sidebarSchema([
                    Forms\Components\Section::make()
                        ->columns(1)
                        ->schema([
                            ...ContentDetailsSchema::make(),
                            ...ContentSettingsSchema::make($form),
                        ]),
                    ContentPublishSection::make(),
                ]),
        ];

    }

    protected static function getEditOptionFormSchema(Forms\Form $form): array
    {
        return [
            ContentTranslationsRepeater::make($form),
            Forms\Components\Grid::make()
                ->statePath('meta')
                ->schema(self::getMetaSchema()),
            Forms\Components\Section::make(__('capell-admin::generic.settings'))
                ->collapsed()
                ->compact()
                ->icon('heroicon-o-cog-6-tooth')
                ->columns()
                ->schema([
                    ...ContentDetailsSchema::make(),
                    ...ContentSettingsSchema::make($form),
                    ContentPublishSection::make(),
                ]),
        ];
    }
}
