<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Workspaces\Models\Workspace;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WorkspaceForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator->components([
            Section::make(__('capell-admin::workspace.approval_history.title'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->collapsible()
                ->visible(fn (?Workspace $record): bool => $record instanceof Workspace && $record->approvals()->exists())
                ->schema([
                    Livewire::make('capell-workspaces::workspace-approval-history'),
                ]),
            TextInput::make('name')
                ->label(__('capell-admin::table.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label(__('capell-admin::table.key'))
                ->maxLength(255)
                ->helperText(__('capell-admin::generic.workspace_slug_helper')),
            Textarea::make('description')
                ->rows(3),
            ColorPicker::make('color'),
            Section::make(__('capell-admin::workspace.workflow.title'))
                ->icon('heroicon-o-user-group')
                ->description(__('capell-admin::workspace.workflow.section_description'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Placeholder::make('roles_overview')
                        ->label(__('capell-admin::workspace.workflow.roles_label'))
                        ->content(new HtmlString(__('capell-admin::workspace.workflow.roles_overview'))),
                    TextInput::make('settings.required_approval_levels')
                        ->label(__('capell-admin::workspace.workflow.approvals_required'))
                        ->helperText(__('capell-admin::workspace.workflow.approvals_required_helper'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(2),
                ]),
        ]);
    }
}
