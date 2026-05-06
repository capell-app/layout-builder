<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Components\Forms\Widget\AdminSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\FormBuilder\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ArticleWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function getFormSchema(Schema $configurator): array
    {
        $operation = $configurator->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                $this->articleSettingsSchema(),
            ],
            'editOption' => [
                Section::make(__('capell-admin::generic.settings'))
                    ->columns()
                    ->compact()
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->collapsed()
                    ->schema([
                        ...SettingsSchema::make($configurator),
                        $this->articleSettingsSchema(),
                    ]),
            ],
            default => [
                Tabs::make()
                    ->visibleOn(['edit', 'editOption'])
                    ->columnSpanFull()
                    ->tabs([
                        WidgetDisplayTab::make([
                            ...SettingsSchema::make($configurator),
                            $this->articleSettingsSchema(),
                        ]),
                        Tab::make(__('capell-admin::generic.admin'))
                            ->statePath('admin')
                            ->icon(config('capell-admin.icon.admin'))
                            ->columns(['md' => 2])
                            ->schema(AdminSchema::make()),
                    ]),
            ],
        };
    }

    protected function articleSettingsSchema(): Fieldset
    {
        return Fieldset::make(__('capell-blog::generic.article'))
            ->statePath('meta')
            ->columns(['default' => 1, 'md' => 2, 'lg' => 4])
            ->columnSpanFull()
            ->schema([
                Checkbox::make('with_date')
                    ->label(__('capell-layout-builder::form.published_date')),
                Checkbox::make('with_next_prev')
                    ->label(__('capell-layout-builder::form.next_prev')),
                Checkbox::make('with_author')
                    ->label(__('capell-layout-builder::form.author')),
            ]);
    }
}
