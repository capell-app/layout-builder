<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\TranslationsRepeater;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Override;

class NavigationBlockConfigurator extends DefaultBlockConfigurator
{
    private const string NavigationPackage = 'capell-app/navigation';

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
        $navigationSelect = NavigationSelect::class;

        return Group::make()
            ->statePath('meta')
            ->schema([
                (CapellCore::isPackageInstalled(self::NavigationPackage) && class_exists($navigationSelect) ? $navigationSelect::make('navigation') : Select::make('navigation'))
                    ->required(),
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
                    BlockDisplayTab::make([
                        DisplaySection::make(),
                        ComponentSection::make(),
                    ]),
                    BlockAdminTab::make(),
                ]),
        ];
    }
}
