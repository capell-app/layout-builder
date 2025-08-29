<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Schemas\Types;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\Media\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\Content\ContentDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentPublishSection;
use Capell\Layout\Filament\Components\Forms\Content\ContentSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\CustomColorInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DefaultContentSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    protected static string $schemaType = SchemaEnum::Content->value;

    public static function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'editOption', 'replicate' => self::getOptionFormSchema($schema),
            default => self::getFormSchema($schema),
        };
    }

    protected static function getMetaSchema(): array
    {
        return [
            IconPicker::make('icon')
                ->label(__('capell-admin::form.icon')),
            MediaLibraryFileUpload::make('image')
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

    protected static function getOptionFormSchema(Schema $schema): array
    {
        return [
            Grid::make()
                ->hiddenOn(['edit', 'editOption'])
                ->columnSpanFull()
                ->schema(ContentDetailsSchema::make($schema)),
            Tabs::make()
                ->columnSpanFull()
                ->schema([
                    self::translationsTab($schema),
                    self::settingsTab($schema),
                ]),
        ];
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            Section::make()
                ->contained(fn (string $operation): bool => $operation === 'created')
                ->hiddenOn('edit')
                ->columnSpanFull()
                ->columns()
                ->schema(ContentDetailsSchema::make($schema)),
            FixedWidthSidebar::make()
                ->mainSchema([
                    ContentTranslationsRepeater::make($schema),
                    Section::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema(self::getMetaSchema()),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['default' => 1, '@lg' => 2])
                        ->schema([
                            ...($schema->getOperation() !== 'create' ? ContentDetailsSchema::make($schema) : []),
                            ...ContentSettingsSchema::make($schema),
                        ]),
                    ContentPublishSection::make(),
                ]),
        ];

    }

    protected static function settingsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.settings'))
            ->icon('heroicon-m-cog-6-tooth')
            ->columns()
            ->schema([
                ...ContentDetailsSchema::make($schema),
                ...ContentSettingsSchema::make($schema),
                ContentPublishSection::make(),
            ]);
    }

    private static function translationsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->schema([
                ContentTranslationsRepeater::make($schema)
                    ->hiddenLabel(),
            ]);
    }
}
