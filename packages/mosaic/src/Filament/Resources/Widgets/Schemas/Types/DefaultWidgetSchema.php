<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Mosaic\Enums\SchemaExtenderEnum;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Components\Forms\ActionsRepeater;
use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class DefaultWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = TypeSchemaEnum::Widget;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Widget->value);
    }

    public function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionSchema($schema),
            'editOption' => $this->getEditOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($schema)
                        ->contained(),
                    ...$this->getExtraSchema($schema),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['@md' => 2])
                        ->schema([
                            ...SettingsSchema::make($schema),
                            MediaLibraryFileUpload::make('image'),
                        ]),
                ]),
        ];
    }

    protected function getEditOptionSchema(Schema $schema): array
    {
        return [
            TranslationsRepeater::make($schema),
            ...$this->getExtraSchema($schema, withSettingsTab: true),
        ];
    }

    protected function getCreateOptionSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            TranslationsRepeater::make($schema),
            ...$this->getExtraSchema($schema),
        ];
    }

    protected function getExtraSchema(Schema $schema, bool $withSettingsTab = false): array
    {
        return [
            $this->getTabs($schema, $withSettingsTab),
        ];
    }

    protected function getTabs(Schema $schema, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                $this->detailsTab(),
                $this->displayTab($schema),
                ...$withSettingsTab ? [$this->settingsTab($schema)] : [],
                WidgetAdminTab::make(),
            ]);
    }

    protected function displayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
                Checkbox::make('reverse_order')
                    ->label(__('capell-mosaic::form.reverse_order'))
                    ->whenTruthy('image'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    protected function detailsTab(): Tab
    {
        return Tab::make('details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                ActionsRepeater::make('actions'),
            ]);
    }

    protected function settingsTab(Schema $schema): Tab
    {
        return WidgetSettingsTab::make($schema);
    }
}
