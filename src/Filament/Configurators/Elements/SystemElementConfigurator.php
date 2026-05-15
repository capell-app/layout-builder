<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Elements;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\TranslationsRepeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class SystemElementConfigurator extends DefaultElementConfigurator
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
            ComponentSection::make(),
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
                    ElementDisplayTab::make([
                        ...$this->getFilesSchema(),
                    ]),
                    ElementAdminTab::make(),
                ]),
        ];
    }
}
