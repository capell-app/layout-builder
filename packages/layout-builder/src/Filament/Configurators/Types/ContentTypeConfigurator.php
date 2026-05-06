<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Types;

use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\ContentStructureSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\RequiredFields;
use Capell\Admin\Filament\Configurators\Types\DefaultTypeConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SectionConfiguratorEnum;
use Filament\FormBuilder\Components\Checkbox;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class ContentTypeConfigurator extends DefaultTypeConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        return [
            ...$this->settingsSchema($configurator),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->adminTab(),
                ]),
            ...$this->statusSchema(),
        ];
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
                    ->default(fn (): string => SectionConfiguratorEnum::Default->name)
                    ->setupOptions(ConfiguratorTypeEnum::Section),
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),
                ContentStructureSelect::make('content_structure'),
                Group::make([
                    Checkbox::make('required_translation')
                        ->label(__('capell-admin::form.required_translations')),
                    RequiredFields::make()
                        ->visibleJs(<<<'JS'
                             $get('required_translation')
                        JS),
                ]),
            ]);
    }
}
