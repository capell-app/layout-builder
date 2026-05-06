<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Schemas;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\ThemeStudio\Admin\Enums\AlignmentOption;
use Capell\ThemeStudio\Admin\Enums\CardStyleOption;
use Capell\ThemeStudio\Admin\Enums\LayoutPresentationOption;
use Capell\ThemeStudio\Admin\Enums\MediaTreatmentOption;
use Capell\ThemeStudio\Admin\Enums\MotionIntensityOption;
use Capell\ThemeStudio\Admin\Enums\NavigationStyleOption;
use Capell\ThemeStudio\Admin\Enums\SpacingOption;
use Capell\ThemeStudio\Admin\Rules\SafeCssColor;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Filament\FormBuilder\Components\ColorPicker;
use Filament\FormBuilder\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ThemeStudioSettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [self::tabs()];
    }

    public static function tabs(): Tabs
    {
        return Tabs::make(__('capell-theme-studio-admin::studio.settings'))
            ->tabs([
                Tab::make(__('capell-theme-studio-admin::studio.tabs.theme'))
                    ->schema([
                        Select::make('draftTheme')
                            ->label(__('capell-theme-studio-admin::studio.draft_theme'))
                            ->options(fn (): array => self::themeOptions())
                            ->reactive()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('draftPreset', null);
                            }),
                        Select::make('draftPreset')
                            ->label(__('capell-theme-studio-admin::studio.draft_preset'))
                            ->options(fn (Get $get): array => self::presetOptions($get('draftTheme'))),
                    ]),
                Tab::make(__('capell-theme-studio-admin::studio.tabs.brand'))
                    ->schema([
                        Fieldset::make(__('capell-theme-studio-admin::studio.fields.colours'))
                            ->schema([
                                ColorPicker::make('brandProfile.primaryColor')
                                    ->label(__('capell-theme-studio-admin::studio.primary_colour'))
                                    ->rule(new SafeCssColor)
                                    ->required(),
                                ColorPicker::make('brandProfile.accentColor')
                                    ->label(__('capell-theme-studio-admin::studio.accent_colour'))
                                    ->rule(new SafeCssColor)
                                    ->required(),
                            ]),
                        Fieldset::make(__('capell-theme-studio-admin::studio.fields.typography'))
                            ->schema([
                                Select::make('brandProfile.headingFont')
                                    ->label(__('capell-theme-studio-admin::studio.heading_font'))
                                    ->options(self::fontOptions())
                                    ->required(),
                                Select::make('brandProfile.bodyFont')
                                    ->label(__('capell-theme-studio-admin::studio.body_font'))
                                    ->options(self::fontOptions())
                                    ->required(),
                            ]),
                    ]),
                Tab::make(__('capell-theme-studio-admin::studio.tabs.presentation'))
                    ->schema([
                        Select::make('brandProfile.spacing')
                            ->label(__('capell-theme-studio-admin::studio.spacing'))
                            ->options(SpacingOption::class)
                            ->required(),
                        Select::make('brandProfile.alignment')
                            ->label(__('capell-theme-studio-admin::studio.alignment'))
                            ->options(AlignmentOption::class)
                            ->required(),
                        Select::make('brandProfile.cardStyle')
                            ->label(__('capell-theme-studio-admin::studio.card_style'))
                            ->options(CardStyleOption::class)
                            ->required(),
                        Select::make('brandProfile.navigationStyle')
                            ->label(__('capell-theme-studio-admin::studio.navigation_style'))
                            ->options(NavigationStyleOption::class)
                            ->required(),
                        Select::make('brandProfile.layoutPresentation')
                            ->label(__('capell-theme-studio-admin::studio.layout_presentation'))
                            ->options(LayoutPresentationOption::class)
                            ->required(),
                        Select::make('brandProfile.motionIntensity')
                            ->label(__('capell-theme-studio-admin::studio.motion_intensity'))
                            ->options(MotionIntensityOption::class)
                            ->required(),
                        Select::make('brandProfile.mediaTreatment')
                            ->label(__('capell-theme-studio-admin::studio.media_treatment'))
                            ->options(MediaTreatmentOption::class)
                            ->required(),
                    ]),
                Tab::make(__('capell-theme-studio-admin::studio.tabs.overrides'))
                    ->schema(fn (): array => self::overrideFieldsets()),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function themeOptions(): array
    {
        return collect(resolve(ThemeRegistry::class)->definitions())
            ->mapWithKeys(fn ($definition): array => [$definition->key => $definition->name])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function fontOptions(): array
    {
        return [
            'inter' => __('capell-theme-studio-admin::studio.fonts.inter'),
            'manrope' => __('capell-theme-studio-admin::studio.fonts.manrope'),
            'playfair' => __('capell-theme-studio-admin::studio.fonts.playfair'),
            'sora' => __('capell-theme-studio-admin::studio.fonts.sora'),
        ];
    }

    /**
     * @return array<int, Fieldset>
     */
    private static function overrideFieldsets(): array
    {
        return collect(resolve(ThemeRegistry::class)->definitions())
            ->map(fn ($definition): Fieldset => Fieldset::make($definition->name)
                ->schema(self::overrideFields($definition->key)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private static function overrideFields(string $themeKey): array
    {
        return [
            ColorPicker::make(sprintf('themeOverrides.%s.primaryColor', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.primary_colour'))
                ->rule(new SafeCssColor),
            ColorPicker::make(sprintf('themeOverrides.%s.accentColor', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.accent_colour'))
                ->rule(new SafeCssColor),
            Select::make(sprintf('themeOverrides.%s.spacing', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.spacing'))
                ->options(SpacingOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
            Select::make(sprintf('themeOverrides.%s.alignment', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.alignment'))
                ->options(AlignmentOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
            Select::make(sprintf('themeOverrides.%s.cardStyle', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.card_style'))
                ->options(CardStyleOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
            Select::make(sprintf('themeOverrides.%s.navigationStyle', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.navigation_style'))
                ->options(NavigationStyleOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
            Select::make(sprintf('themeOverrides.%s.layoutPresentation', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.layout_presentation'))
                ->options(LayoutPresentationOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
            Select::make(sprintf('themeOverrides.%s.motionIntensity', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.motion_intensity'))
                ->options(MotionIntensityOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
            Select::make(sprintf('themeOverrides.%s.mediaTreatment', $themeKey))
                ->label(__('capell-theme-studio-admin::studio.media_treatment'))
                ->options(MediaTreatmentOption::class)
                ->placeholder(__('capell-theme-studio-admin::studio.inherit')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function presetOptions(mixed $themeKey): array
    {
        if (! is_string($themeKey) || $themeKey === '') {
            return [];
        }

        return resolve(ThemeRegistry::class)
            ->definition($themeKey)
            ->presetOptions();
    }
}
