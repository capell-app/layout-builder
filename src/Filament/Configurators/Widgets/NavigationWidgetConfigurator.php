<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\TranslationsRepeater;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Override;

class NavigationWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        $operation = $configurator->getOperation();

        return match ($operation) {
            'createOption' => $this->getCreateOptionSchema($configurator),
            'editOption', 'replicate' => $this->getEditOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    #[Override]
    protected function getCreateOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            Section::make()
                ->schema([$this->navigationSelect()]),
            TranslationsRepeater::make($configurator)
                ->contained(),
        ];
    }

    protected function navigationSelect(): Group
    {
        $navigationField = TextInput::make('navigation');

        if (CapellCore::isPackageAvailable('capell-app/navigation') && CapellCore::isPackageInstalled('capell-app/navigation')) {
            $navigationSelect = NavigationSelect::class;

            if (class_exists($navigationSelect)) {
                $navigationField = $navigationSelect::make('navigation')->useKeyAsValue();
            }
        }

        return Group::make()
            ->statePath('meta')
            ->schema([
                $navigationField->required(),
            ]);
    }

    #[Override]
    protected function getEditOptionSchema(Schema $configurator): array
    {
        return [
            $this->navigationSelect(),
            TranslationsRepeater::make($configurator),
        ];
    }

    #[Override]
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
                    SettingsSchema::make($configurator, [$this->navigationSelect()]),
                    contained: true,
                ),
            Tabs::make()
                ->visibleOn(['edit', 'editOption'])
                ->columnSpanFull()
                ->tabs([
                    ...WidgetPresentationTabs::make(),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
