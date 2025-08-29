<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetResultsSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Override;

class RelatedWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'createOption', 'editOption', 'replicate' => static::getOptionSchema($schema),
            default => static::getFormSchema($schema),
        };
    }

    protected static function getOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            WidgetTranslationsRepeater::make($schema)
                ->section(fn (string $operation): bool => $operation === 'create'),
        ];
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    WidgetTranslationsRepeater::make($schema),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($schema)),
                ]),
            Tabs::make()
                ->visibleOn('edit')
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        WidgetDisplaySection::make([
                            Group::make([
                                Checkbox::make('exclude_parent')
                                    ->label(__('capell-layout::form.exclude_parent')),
                                Select::make('exclude_types')
                                    ->label(__('capell-layout::form.exclude_types'))
                                    ->helperText(__('capell-layout::generic.exclude_types_info'))
                                    ->multiple()
                                    ->options(
                                        fn (): array => CapellCore::getModel(ModelEnum::Type)::query()
                                            ->pageType()
                                            ->pluck('name', 'key')
                                            ->toArray()
                                    ),
                            ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('limit')
                                        ->label(__('capell-admin::form.limit')),
                                    Checkbox::make('pagination')
                                        ->label(__('capell-admin::form.pagination'))
                                        ->default(true),
                                    CacheFrequencySelect::make('cache_frequency'),
                                ]),
                            Fieldset::make(__('capell-admin::generic.display_settings'))
                                ->columns(['default' => 1, 'md' => 2, 'lg' => 3, 'xl' => 4])
                                ->columnSpanFull()
                                ->schema(WidgetResultsSettingsSchema::make()),
                        ]),
                        WidgetComponentFilesSection::make(),
                    ])
                        ->statePath('meta'),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
