<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Filament\Resources\Users\RelationManagers;

use BackedEnum;
use Capell\AgentBridge\Models\CapellAgentBridgeToken;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

final class AgentBridgeTokensRelationManager extends RelationManager
{
    protected static string|BackedEnum|null $icon = Heroicon::OutlinedKey;

    protected static string $relationship = 'agentBridgeTokens';

    protected static bool $shouldSkipAuthorization = true;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-agent-bridge::admin.tokens');
    }

    /**
     * @param  Builder<CapellAgentBridgeToken>  $query
     * @return Builder<CapellAgentBridgeToken>
     */
    public static function scopedQueryForUser(Builder $query, Model $user): Builder
    {
        return $query
            ->where('user_type', $user->getMorphClass())
            ->where('user_id', $user->getKey());
    }

    public function getRelationship(): Relation|Builder
    {
        return self::scopedQueryForUser(CapellAgentBridgeToken::query(), $this->ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-agent-bridge::admin.token_name'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('capell-agent-bridge::admin.token_status'))
                    ->state(fn (CapellAgentBridgeToken $record): string => $record->isExpired()
                        ? __('capell-agent-bridge::admin.token_expired')
                        : __('capell-agent-bridge::admin.token_active')),
                TextColumn::make('last_used_at')
                    ->label(__('capell-agent-bridge::admin.last_used_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('capell-agent-bridge::admin.expires_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[Override]
    protected function canCreate(): bool
    {
        return false;
    }
}
