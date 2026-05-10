<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Tables;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\ApproveAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\CompareAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\PreviewAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\PublishAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\RejectAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\RequestChangesAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\RollbackAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\SaveAsDraftAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\ScheduleAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\SchedulerMetadataAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\SubmitForApprovalAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\UnscheduleAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\ValidateAction;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PublishingStudioTable implements TableConfigurator
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
                SelectFilter::make('kind')
                    ->label(__('capell-admin::workspace.kind_label'))
                    ->options(WorkspaceKindEnum::class),
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
            ->emptyStateHeading(__('capell-admin::generic.no_publishing-studio'))
            ->emptyStateDescription(__('capell-admin::generic.no_publishing-studio_description'))
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
            SchedulerMetadataAction::make(),
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
            TextColumn::make('kind')
                ->label(__('capell-admin::workspace.kind_label'))
                ->badge()
                ->color(fn (Workspace $record): string => $record->kind?->getColor() ?? 'gray')
                ->icon(function (Workspace $record): string {
                    $icon = $record->kind?->getIcon();

                    if ($icon instanceof Heroicon) {
                        return 'heroicon-' . $icon->value;
                    }

                    return $icon ?? '';
                })
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
