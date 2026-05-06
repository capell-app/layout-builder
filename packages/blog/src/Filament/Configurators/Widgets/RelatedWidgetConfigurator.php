<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\TranslationsRepeater;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\FormBuilder\Components\Checkbox;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class RelatedWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        $operation = $configurator->getOperation();

        return match ($operation) {
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator)
                ->contained(fn (string $operation): bool => $operation === 'create'),
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
                    TranslationsRepeater::make($configurator),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($configurator),
                    contained: true,
                ),
            Tabs::make()
                ->visibleOn('edit')
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        DisplaySection::make([
                            Group::make([
                                Checkbox::make('exclude_parent')
                                    ->label(__('capell-layout-builder::form.exclude_parent')),
                                Select::make('exclude_types')
                                    ->label(__('capell-layout-builder::form.exclude_types'))
                                    ->helperText(__('capell-layout-builder::generic.exclude_types_info'))
                                    ->multiple()
                                    ->options(
                                        function (): array {
                                            /** @var class-string<Type> $model */
                                            $model = Type::class;

                                            return $model::query()
                                                ->pageType()
                                                ->pluck('name', 'key')
                                                ->toArray();
                                        },
                                    ),
                            ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('limit')
                                        ->label(__('capell-layout-builder::form.limit')),
                                    Checkbox::make('pagination')
                                        ->label(__('capell-layout-builder::form.pagination'))
                                        ->default(true),
                                    CacheFrequencySelect::make('cache_frequency'),
                                ]),
                            ...ResultsSchema::make($configurator),
                        ]),
                        ComponentSection::make()
                            ->statePath('meta'),
                    ]),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
