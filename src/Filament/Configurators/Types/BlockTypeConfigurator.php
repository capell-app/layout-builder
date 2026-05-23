<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Types;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\RequiredFields;
use Capell\Admin\Filament\Configurators\Types\DefaultTypeConfigurator;
use Capell\Core\Support\Media\ImageSourcePresets;
use Capell\LayoutBuilder\Enums\BlockConfiguratorEnum;
use Capell\LayoutBuilder\Enums\BlockTypeGroupEnum;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\DisplaySection;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class BlockTypeConfigurator extends DefaultTypeConfigurator
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

    #[Override]
    protected function getGroupField(): Component
    {
        return CustomSelectGroup::make(
            'group',
            options: fn (): array => collect(BlockTypeGroupEnum::cases())
                ->mapWithKeys(fn (BlockTypeGroupEnum $case): array => [$case->value => $case->name])
                ->all(),
        )
            ->label(__('capell-admin::form.group'))
            ->helperText(__('capell-admin::generic.type_group_info'));
    }

    protected function adminTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columnSpanFull()
            ->columns()
            ->schema([
                $this->typeConfiguratorSelect(static::getKey()),
                ConfiguratorSelect::make('configurator')
                    ->label(__('capell-admin::form.admin_form_configurator'))
                    ->helperText(__('capell-admin::generic.admin_form_configurator_info'))
                    ->default(fn (): string => BlockConfiguratorEnum::Default->name)
                    ->setupOptions(ConfiguratorTypeEnum::Block),
                ConfiguratorSelect::make('layout_block_configurator')
                    ->label(__('capell-admin::form.layout_block_configurator'))
                    ->helperText(__('capell-admin::generic.layout_block_configurator_info'))
                    ->default('Default')
                    ->setupOptions(ConfiguratorTypeEnum::LayoutBlock),
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),
                AssetTypeSelect::make('asset_types')
                    ->multiple(),
                Select::make('image_source_policy.image')
                    ->label(__('capell-admin::form.image_source_policy'))
                    ->helperText(__('capell-admin::form.image_source_policy_helper'))
                    ->options(ImageSourcePresets::options())
                    ->placeholder(__('capell-admin::generic.default')),
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
                ComponentSection::make(),
            ]);
    }
}
