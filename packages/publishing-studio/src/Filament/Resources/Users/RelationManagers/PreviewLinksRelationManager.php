<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Users\RelationManagers;

use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\Concerns\ScopesPublishingStudioRecordsToUser;
use Capell\PublishingStudio\Models\PreviewLink;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class PreviewLinksRelationManager extends RelationManager
{
    use ScopesPublishingStudioRecordsToUser;

    protected static bool $shouldSkipAuthorization = true;

    public static function scopedQueryForUser(Model $user): Builder
    {
        $morphKey = static::userMorphKey($user);

        return PreviewLink::query()
            ->withoutGlobalScopes()
            ->where('issued_by_type', $morphKey['type'])
            ->where('issued_by_id', $morphKey['id']);
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-publishing-studio::workspace.user_bridge.preview_links');
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
                TextColumn::make('issued_at')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.issued_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.expires_at'))
                    ->dateTime(),
                IconColumn::make('revoked_at')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.revoked'))
                    ->boolean(),
            ])
            ->defaultSort('issued_at', 'desc');
    }
}
