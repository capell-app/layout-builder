<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Tables;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\ApproveAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\CompareAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\PreviewAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\PublishAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\RejectAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\RequestChangesAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\RollbackAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\SaveAsDraftAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\ScheduleAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\SubmitForApprovalAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\UnscheduleAction;
use Capell\Workspaces\Filament\Resources\Workspaces\Actions\ValidateAction;
use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WorkspacesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns(static::getTableColumns())
            ->recordActions(static::getRecordActions())
            ->filters([
                SelectFilter::make('status')
                    ->label(__('capell-admin::table.status'))
                    ->options(WorkspaceStatusEnum::class),
                SelectFilter::make('created_by')
                    ->label(__('capell-admin::table.created_by'))
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ])
            ->emptyStateHeading(__('capell-admin::generic.no_workspaces'))
            ->emptyStateDescription(__('capell-admin::generic.no_workspaces_description'))
            ->emptyStateIcon('heroicon-o-beaker');
    }

    /**
     * @return array<int, Action|ActionGroup>
     */
    protected static function getRecordActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth(Width::ScreenLarge)
                ->slideOver()
                ->hidden(fn (Workspace $record): bool => $record->trashed()),
            SaveAsDraftAction::make(),
            SubmitForApprovalAction::make(),
            ApproveAction::make(),
            RequestChangesAction::make(),
            RejectAction::make(),
            PublishAction::make(),
            ScheduleAction::make(),
            UnscheduleAction::make(),
            PreviewAction::make(),
            ...static::getContributorRecordActions(),
            ValidateAction::make(),
            CompareAction::make(),
            RollbackAction::make(),
            ActionGroup::make([
                DeleteAction::make(),
                RestoreAction::make(),
            ])
                ->color('gray'),
        ];
    }

    /**
     * @return array<int, Action|ActionGroup>
     */
    protected static function getContributorRecordActions(): array
    {
        /** @var iterable<WorkspaceTableActionContributor> $contributors */
        $contributors = app()->tagged(WorkspaceTableActionContributor::TAG);

        $actions = [];

        foreach ($contributors as $contributor) {
            array_push($actions, ...$contributor->actions());
        }

        return $actions;
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->icon(fn (Workspace $record): string => $record->color !== null && $record->color !== ''
                    ? 'heroicon-m-circle-stack'
                    : '')
                ->description(fn (Workspace $record): ?string => $record->description)
                ->searchable([
                    'name',
                    'slug',
                    'description',
                ]),
            TextColumn::make('status')
                ->label(__('capell-admin::table.status'))
                ->badge()
                ->color(fn (Workspace $record): string => $record->status?->getColor() ?? 'gray')
                ->sortable(),
            TextColumn::make('latestApproval.notes')
                ->label(__('capell-admin::table.latest_review_note'))
                ->placeholder('—')
                ->limit(80)
                ->tooltip(fn (Workspace $record): ?string => $record->latestApproval?->notes)
                ->description(fn (Workspace $record): ?string => $record->latestApproval?->action?->getLabel())
                ->toggleable(),
            TextColumn::make('creator.name')
                ->label(__('capell-admin::table.owner'))
                ->toggleable(),
            DateColumn::make('updated_at'),
            DateColumn::make('created_at')
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('deleted_at')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
