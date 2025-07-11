<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetResultsSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Forms;

class RelatedWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate', 'editOption' => [
                WidgetTranslationsRepeater::make($form)
                    ->section(fn (string $operation): bool => $operation === 'create'),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($form),
                    ])
                    ->sidebarSchema([
                        Forms\Components\Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($form)),
                    ]),
                Forms\Components\Tabs::make('tabs')
                    ->visibleOn('edit')
                    ->columnSpanFull()
                    ->tabs([
                        WidgetDisplayTab::make([
                            Forms\Components\Group::make()
                                ->statePath('meta')
                                ->columns()
                                ->schema([
                                    Forms\Components\Group::make([
                                        Forms\Components\Checkbox::make('exclude_parent')
                                            ->label(__('capell-layout::form.exclude_parent')),
                                        Forms\Components\Select::make('exclude_types')
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
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('limit')
                                                ->label(__('capell-admin::form.limit')),
                                            Forms\Components\Checkbox::make('pagination')
                                                ->label(__('capell-admin::form.pagination'))
                                                ->default(true),
                                            CacheFrequencySelect::make('cache_frequency'),
                                        ]),
                                    Forms\Components\Fieldset::make(__('capell-admin::generic.display_settings'))
                                        ->columns(['default' => 1, 'md' => 2, 'lg' => 3, 'xl' => 4])
                                        ->columnSpanFull()
                                        ->schema(WidgetResultsSettingsSchema::make()),
                                    WidgetDisplaySection::make(),
                                    WidgetComponentFilesSection::make(),
                                ]),
                        ]),
                        WidgetAdminTab::make(),
                    ]),
            ],
        };
    }
}
