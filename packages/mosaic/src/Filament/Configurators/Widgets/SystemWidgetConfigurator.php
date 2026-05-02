<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class SystemWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        $operation = $configurator->getOperation();

        return match ($operation) {
            'createOption', 'editOption',  'replicate' => $this->getOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getFilesSchema(): array
    {
        return [
            DisplaySection::make(),
            ComponentSection::make()
                ->statePath('meta'),
        ];
    }

    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator)
                ->contained(fn (string $operation): bool => $operation === 'create'),
            ...$this->getFilesSchema(),
            Section::make(__('capell-admin::generic.settings'))
                ->columns()
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->collapsed()
                ->schema(SettingsSchema::make($configurator)),
        ];
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($configurator)
                        ->contained(),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($configurator),
                    contained: true,
                ),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        ...$this->getFilesSchema(),
                    ]),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
