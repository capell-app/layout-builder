<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidadminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetResultsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
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
            CreateWidgetDetailsSchema::make($schema),
            WidgetTranslationsRepeater::make($schema)
                ->contained(fn (string $operation): bool => $operation === 'create'),
            ...WidgetSettingsSchema::make($schema),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    WidgetTranslationsRepeater::make($schema)
                        ->contained(),
                ])
                ->sidebarSchema(
                    WidgetSettingsSchema::make($schema),
                    contained: true,
                ),
            Tabs::make()
                ->visibleOn('edit')
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        WidgetDisplaySection::make([
                            TextInput::make('limit')
                                ->label(__('capell-admin::form.limit')),
                            Checkbox::make('pagination')
                                ->label(__('capell-admin::form.pagination'))
                                ->default(true),
                            CacheFrequencySelect::make('cache_frequency'),
                            Fieldset::make(__('capell-admin::generic.display_settings'))
                                ->columns(['default' => 1, 'md' => 2, 'lg' => 3, 'xl' => 4])
                                ->columnSpanFull()
                                ->schema(WidgetResultsSchema::make()),
                        ]),
                        WidgetComponentFilesSection::make()
                            ->statePath('meta'),
                    ]),
                    WidadminTab::make(),
                ]),
        ];
    }
}
