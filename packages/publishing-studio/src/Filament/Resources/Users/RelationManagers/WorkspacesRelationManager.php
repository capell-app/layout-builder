<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Users\RelationManagers;

use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\Concerns\ScopesPublishingStudioRecordsToUser;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class WorkspacesRelationManager extends RelationManager
{
    use ScopesPublishingStudioRecordsToUser;

    protected static bool $shouldSkipAuthorization = true;

    public static function scopedQueryForUser(Model $user): Builder
    {
        return Workspace::query()
            ->withoutGlobalScopes()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('created_by', $user->getKey())
                    ->orWhere('updated_by', $user->getKey());
            });
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-publishing-studio::workspace.user_bridge.workspaces');
    }

    public function getRelationship(): Relation|Builder
    {
        return static::scopedQueryForUser($this->getOwnerRecord());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-admin::table.name'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.status'))
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label(__('capell-admin::table.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
