<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Actions\ResetLayoutContainerThemeSettingsAction;
use Capell\LayoutBuilder\Contracts\Extenders\LayoutContainerSchemaExtender;
use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\BackgroundSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\BorderSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\ColumnInput;
use Capell\LayoutBuilder\Filament\Components\Forms\ContainerWidthSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\HtmlClassInput;
use Capell\LayoutBuilder\Filament\Components\Forms\MarginSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\PaddingSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\SpacingSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\TagSelect;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @method array<array-key, Htmlable> make(Schema $configurator, ?LayoutContainerSchemaContextData $context = null)
 */
class DefaultLayoutContainerConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::LayoutContainer;

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutContainer->value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function make(Schema $configurator, ?LayoutContainerSchemaContextData $context = null): array
    {
        $context ??= LayoutContainerSchemaContextData::fromSchema($configurator);

        return [
            Section::make(__('capell-layout-builder::generic.layout_and_appearance'))
                ->statePath('meta')
                ->extraAttributes(['data-layout-container-section' => 'appearance'])
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema([
                    ContainerWidthSelect::make(),
                    Select::make('alignment')
                        ->label(__('capell-layout-builder::form.alignment'))
                        ->options(ContainerAlignmentEnum::class)
                        ->default(ContainerAlignmentEnum::Stretch->value),
                    SpacingSelect::make('spacing')
                        ->helperText(__('capell-admin::generic.container_spacing_help')),
                    BorderSelect::make('border'),
                    PaddingSelect::make('padding')
                        ->helperText(__('capell-layout-builder::generic.padding_base_helper')),
                    Toggle::make('responsive_padding_enabled')
                        ->label(__('capell-layout-builder::form.customise_padding_by_breakpoint'))
                        ->extraAttributes(['data-layout-container-control' => 'responsive-padding'])
                        ->helperText(__('capell-layout-builder::generic.responsive_padding_helper'))
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Toggle $component, Get $get): void {
                            $component->state(filled($get('padding_tablet')) || filled($get('padding_desktop')));
                        })
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            if ($state === true) {
                                return;
                            }

                            $set('padding_tablet', null);
                            $set('padding_desktop', null);
                        }),
                    PaddingSelect::make('padding_tablet')
                        ->label(__('capell-layout-builder::form.padding_tablet'))
                        ->extraAttributes(['data-layout-container-field' => 'padding-tablet'])
                        ->visible(fn (Get $get): bool => $get('responsive_padding_enabled') === true),
                    PaddingSelect::make('padding_desktop')
                        ->label(__('capell-layout-builder::form.padding_desktop'))
                        ->visible(fn (Get $get): bool => $get('responsive_padding_enabled') === true),
                ]),
            Section::make(__('capell-admin::generic.background'))
                ->collapsed()
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema(
                    BackgroundSchema::make(
                        backgroundCollectionUsing: fn (Get $get): string => $get('key') . '-background',
                    ),
                ),
            ...$this->themeSettingsSections($configurator, $context),
            Section::make(__('capell-layout-builder::generic.advanced'))
                ->statePath('meta')
                ->collapsed()
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema([
                    ColumnInput::make('colspan')
                        ->label(__('capell-layout-builder::form.colspan'))
                        ->helperText(__('capell-admin::generic.colspan_info'))
                        ->default(12),
                    ColumnInput::make('column_start')
                        ->label(__('capell-layout-builder::form.column_start')),
                    CheckboxList::make('hidden_on')
                        ->label(__('capell-layout-builder::form.hide_on'))
                        ->options(ResponsiveVisibilityEnum::class)
                        ->default([])
                        ->columns(3),
                    TagSelect::make('tag'),
                    HtmlClassInput::make('html_class'),
                    TextInput::make('override_columns')
                        ->label(__('capell-layout-builder::form.override_columns'))
                        ->helperText(__('capell-admin::generic.override_columns_info')),
                    MarginSelect::make('margin')
                        ->helperText(__('capell-layout-builder::generic.legacy_margin_helper')),
                ]),
        ];
    }

    /**
     * @return array<int, Section>
     */
    private function themeSettingsSections(Schema $configurator, LayoutContainerSchemaContextData $context): array
    {
        $components = [];
        $themeLabel = null;
        $themeKey = null;

        foreach (static::getExtenders() as $extender) {
            if (! $extender instanceof LayoutContainerSchemaExtender) {
                continue;
            }

            if (! $this->supportsContext($extender, $context)) {
                continue;
            }

            $extendedComponents = $extender->extendContainerComponents($configurator, $context);

            if ($extendedComponents === []) {
                continue;
            }

            $themeLabel ??= $extender->themeLabel();
            $themeKey ??= $extender->themeKey();
            array_push($components, ...$extendedComponents);
        }

        if ($components === [] || $themeLabel === null || $themeKey === null) {
            return [];
        }

        return [
            Section::make(__('capell-layout-builder::generic.theme_settings_heading', [
                'theme' => $themeLabel,
            ]))
                ->description(__('capell-layout-builder::generic.theme_settings_description'))
                ->statePath('meta.theme_settings.' . $themeKey)
                ->extraAttributes(['data-layout-container-section' => 'theme'])
                ->collapsed()
                ->icon(Heroicon::OutlinedSwatch)
                ->headerActions([
                    Action::make('reset_theme_settings')
                        ->label(__('capell-layout-builder::form.reset_theme_settings'))
                        ->extraAttributes(['data-layout-container-action' => 'reset-theme-settings'])
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading(__('capell-layout-builder::generic.reset_theme_settings_heading'))
                        ->modalDescription(__('capell-layout-builder::generic.reset_theme_settings_description'))
                        ->action(function (Get $get, Set $set) use ($themeKey): void {
                            $meta = $get('../../../meta');
                            $meta = is_array($meta) ? $meta : [];

                            $set('../../../meta', ResetLayoutContainerThemeSettingsAction::run($meta, $themeKey));
                        }),
                ])
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema($components),
        ];
    }

    private function supportsContext(LayoutContainerSchemaExtender $extender, LayoutContainerSchemaContextData $context): bool
    {
        return $context->themeKey !== null
            && $extender->themeKey() === $context->themeKey
            && $extender->supports($context);
    }
}
