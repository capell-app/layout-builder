<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Users\RelationManagers;

use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\Concerns\ScopesPublishingStudioRecordsToUser;
use Capell\PublishingStudio\Models\Version;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class VersionsRelationManager extends RelationManager
{
    use ScopesPublishingStudioRecordsToUser;

    protected static bool $shouldSkipAuthorization = true;

    public static function scopedQueryForUser(Model $user): Builder
    {
        $morphKey = static::userMorphKey($user);

        return Version::query()
            ->withoutGlobalScopes()
            ->where('published_by_type', $morphKey['type'])
            ->where('published_by_id', $morphKey['id']);
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-publishing-studio::workspace.user_bridge.versions');
    }

    public function getRelationship(): Relation|Builder
    {
        return static::scopedQueryForUser($this->getOwnerRecord());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.version'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('capell-admin::table.name')),
                IconColumn::make('is_live')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.live'))
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label(__('capell-publishing-studio::workspace.user_bridge.published_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
