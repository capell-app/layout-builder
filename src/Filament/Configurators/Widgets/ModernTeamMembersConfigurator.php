<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Enums\ModernGridColumnCount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Team Members Widget
 *
 * Provides admin panel controls for customizing team member grid
 * layout and display options.
 */
class ModernTeamMembersConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::widgets.common.section_content'))
                ->description(__('capell-layout-builder::widgets.modern.team_members.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::widgets.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::widgets.modern.team_members.title_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::widgets.common.section_layout'))
                ->description(__('capell-layout-builder::widgets.modern.team_members.section_layout_description'))
                ->schema([
                    Select::make('data.columns')
                        ->label(__('capell-layout-builder::widgets.common.grid_columns_label'))
                        ->options(ModernGridColumnCount::class)
                        ->default('3')
                        ->helperText(__('capell-layout-builder::widgets.common.grid_columns_helper')),
                ])->columns(1),

            Section::make(__('capell-layout-builder::widgets.common.section_display'))
                ->description(__('capell-layout-builder::widgets.common.section_display_description'))
                ->schema([
                    Toggle::make('data.customizable')
                        ->label(__('capell-layout-builder::widgets.common.admin_hints_label'))
                        ->default(true)
                        ->helperText(__('capell-layout-builder::widgets.common.customize_message_helper')),
                ])->columns(1),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'title' => 'Our Team',
            'columns' => '3',
            'customizable' => true,
        ];
    }
}
