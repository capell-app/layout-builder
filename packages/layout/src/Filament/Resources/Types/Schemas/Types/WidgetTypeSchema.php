<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Types\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ContentEditorSelect;
use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Admin\Filament\Resources\Types\Schemas\Types\DefaultTypeSchema;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Enums\WidgetTypeGroupEnum;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class WidgetTypeSchema extends DefaultTypeSchema
{
    #[Override]
    public static function make(Schema $schema): array
    {
        return [
            ...self::getSettingsSchema($schema),
            ...static::getStatusSchema(),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    static::getFrontendTab(),
                    static::getAdminTab(),
                ]),
        ];
    }

    protected static function getGroupField(): Component
    {
        return CustomSelectGroup::make(
            'group',
            options: fn (): array => collect(WidgetTypeGroupEnum::cases())
                ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                ->toArray()
        );
    }

    protected static function getAdminTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columnSpanFull()
            ->columns()
            ->schema([
                SchemaSelect::make('schema')
                    ->default(fn (): string => WidgetTypeSchema::getKey())
                    ->setupOptions(SchemaEnum::Widget->value),

                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),

                AssetTypeSelect::make('asset_types')
                    ->multiple(),

                ContentEditorSelect::make('content_editor'),

                Checkbox::make('exclude_from_selection')
                    ->label(__('capell-admin::form.exclude_from_selection')),
            ]);
    }

    protected static function getFrontendTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.frontend'))
            ->statePath('meta')
            ->icon('heroicon-m-cog-6-tooth')
            ->columns()
            ->schema([
                WidgetDisplaySection::make(),
                WidgetComponentFilesSection::make(),
            ]);
    }
}
