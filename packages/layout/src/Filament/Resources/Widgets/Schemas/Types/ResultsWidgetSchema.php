<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\PageModelSelect;
use Capell\Layout\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Layout\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class ResultsWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'createOption', 'replicate', 'editOption' => $this->getOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getOptionSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            TranslationsRepeater::make($schema, components: [
                Group::make()
                    ->statePath('meta')
                    ->schema([
                        ContentEditor::make('no_results')
                            ->label(__('capell-admin::form.no_results'))
                            ->hint(__('capell-admin::generic.no_results_info')),
                    ]),
            ])
                ->contained(fn (string $operation): bool => $operation === 'create'),
            $this->getTabs($schema),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($schema)
                        ->contained(),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($schema),
                    contained: true,
                ),
            $this->getTabs($schema),
        ];
    }

    protected function getTabs(Schema $schema, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('capell-admin::generic.frontend'))
                    ->schema([
                        DisplaySection::make([
                            TextInput::make('limit')
                                ->label(__('capell-layout::form.limit')),
                            Checkbox::make('pagination')
                                ->label(__('capell-layout::form.pagination'))
                                ->default(true),
                            CacheFrequencySelect::make('cache_frequency'),
                            ...ResultsSchema::make($schema),
                        ]),
                        ComponentSection::make()
                            ->statePath('meta'),
                        Grid::make()
                            ->statePath('meta')
                            ->columnSpanFull()
                            ->schema([
                                PageModelSelect::make('page_model'),
                            ]),
                    ]),
                WidgetAdminTab::make(),
            ]);
    }
}
