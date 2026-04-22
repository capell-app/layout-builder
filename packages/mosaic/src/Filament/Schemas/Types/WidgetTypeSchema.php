<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\RequiredFields;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Admin\Filament\Schemas\Types\DefaultTypeSchema;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Enums\WidgetSchemaEnum;
use Capell\Mosaic\Enums\WidgetTypeGroupEnum;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class WidgetTypeSchema extends DefaultTypeSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        return [
            ...$this->settingsSchema($schema),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->frontendTab(),
                    $this->adminTab(),
                ]),
            ...$this->statusSchema(),
        ];
    }

    protected function getGroupField(): Component
    {
        return CustomSelectGroup::make(
            'group',
            options: fn (): array => collect(WidgetTypeGroupEnum::cases())
                ->mapWithKeys(fn (WidgetTypeGroupEnum $case): array => [$case->value => $case->name])
                ->all(),
        )
            ->label('Group');
    }

    protected function adminTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columnSpanFull()
            ->columns()
            ->schema([
                SchemaSelect::make('schema')
                    ->default(fn (): string => WidgetSchemaEnum::Default->name)
                    ->setupOptions(TypeSchemaEnum::Widget),
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),
                AssetTypeSelect::make('asset_types')
                    ->multiple(),
                RequiredFields::make(),
            ]);
    }

    protected function frontendTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.frontend'))
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->columns()
            ->schema([
                DisplaySection::make(),
                ComponentSection::make()
                    ->statePath('meta'),
            ]);
    }
}
