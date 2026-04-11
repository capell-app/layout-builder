<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Schemas\Types;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\CallToActionText;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PageSelect;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\TypeSchemaEnum;
use Capell\Layout\Filament\Components\Forms\Content\DetailsSchema;
use Capell\Layout\Filament\Components\Forms\Content\SettingsSchema;
use Capell\Layout\Filament\Components\Forms\Content\TranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\CustomColorInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DefaultContentSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = TypeSchemaEnum::Content;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Content->value);
    }

    public function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'editOption', 'replicate' => $this->getOptionFormSchema($schema),
            default => $this->getFormSchema($schema),
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

    protected function getOptionFormSchema(Schema $schema): array
    {
        return [
            ...DetailsSchema::make($schema),
            TranslationsRepeater::make($schema)
                ->hiddenLabel()
                ->contained(),
            ...SettingsSchema::make($schema),
            MediaLibraryFileUpload::make('image'),
            PublishSchema::make($schema),
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

    protected function settingsTab(Schema $schema): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::generic.settings'))
            ->statePath('meta')
            ->columns()
            ->schema($this->getMetaSchema());
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
}
