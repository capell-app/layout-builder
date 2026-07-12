<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
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

    /**
     * @return array<array-key, mixed>
     */
    protected function presentationTabs(): array
    {
        return WidgetPresentationTabs::make(
            renderingSchema: [
                Grid::make(2)
                    ->statePath('meta')
                    ->visible(fn (Get $get): bool => $get('meta.component') === 'capell.widget.page.breadcrumbs')
                    ->schema([
                        Checkbox::make('show_home')
                            ->label('Show home')
                            ->default(true),
                        Checkbox::make('show_parent')
                            ->label('Show parent page')
                            ->default(true),
                        Checkbox::make('show_current_page')
                            ->label('Show current page')
                            ->default(true),
                        TextInput::make('minimum_items')
                            ->label('Minimum visible crumbs')
                            ->helperText('Hide breadcrumbs until this many crumbs remain after hidden items are removed.')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ]),
            ],
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator)
                ->contained(fn (string $operation): bool => $operation === 'create'),
            Tabs::make()
                ->columnSpanFull()
                ->tabs($this->presentationTabs()),
            Section::make(__('capell-admin::generic.settings'))
                ->columns()
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->collapsed()
                ->schema(SettingsSchema::make($configurator)),
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
                    SettingsSchema::make($configurator),
                    contained: true,
                ),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    ...$this->presentationTabs(),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
