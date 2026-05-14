<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Types;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\RequiredFields;
use Capell\Admin\Filament\Configurators\Types\DefaultTypeConfigurator;
use Capell\Core\LayoutBuilder\Enums\WidgetConfiguratorEnum;
use Capell\Core\LayoutBuilder\Enums\WidgetTypeGroupEnum;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\DisplaySection;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class WidgetTypeConfigurator extends DefaultTypeConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        return [
            ...$this->settingsSchema($configurator),
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
                ConfiguratorSelect::make('configurator')
                    ->default(fn (): string => WidgetConfiguratorEnum::Default->name)
                    ->setupOptions(ConfiguratorTypeEnum::Widget),
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
