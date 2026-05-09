<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Users\RelationManagers;

use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\Concerns\ScopesPublishingStudioRecordsToUser;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class WorkspaceFieldCommentsRelationManager extends RelationManager
{
    use ScopesPublishingStudioRecordsToUser;

    protected static bool $shouldSkipAuthorization = true;

    public static function scopedQueryForUser(Model $user): Builder
    {
        $morphKey = static::userMorphKey($user);

        return WorkspaceFieldComment::query()
            ->withoutGlobalScopes()
            ->where('author_type', $morphKey['type'])
            ->where('author_id', $morphKey['id']);
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-publishing-studio::workspace.user_bridge.field_comments');
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
                TextColumn::make('field_path')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.field_path')),
                TextColumn::make('body')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.comment'))
                    ->limit(80),
                TextColumn::make('created_at')
                    ->label(__('capell-admin::table.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
