<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Users\RelationManagers;

use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\Concerns\ScopesPublishingStudioRecordsToUser;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class WorkspaceReviewAssignmentsRelationManager extends RelationManager
{
    use ScopesPublishingStudioRecordsToUser;

    protected static bool $shouldSkipAuthorization = true;

    public static function scopedQueryForUser(Model $user): Builder
    {
        $morphKey = static::userMorphKey($user);

        return WorkspaceReviewAssignment::query()
            ->withoutGlobalScopes()
            ->where('reviewer_type', $morphKey['type'])
            ->where('reviewer_id', $morphKey['id']);
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-publishing-studio::workspace.user_bridge.review_assignments');
    }

    public function getRelationship(): Relation|Builder
    {
        return static::scopedQueryForUser($this->getOwnerRecord());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workspace.name')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.workspace')),
                TextColumn::make('required_for')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.required_for')),
                TextColumn::make('decision')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.decision'))
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label(__('capell-admin::table.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
