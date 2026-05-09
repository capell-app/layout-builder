<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Filament\Resources\Users\RelationManagers;

use BackedEnum;
use Capell\AgentBridge\Models\CapellAgentBridgeConfirmation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

final class AgentBridgeConfirmationsRelationManager extends RelationManager
{
    protected static string|BackedEnum|null $icon = Heroicon::OutlinedShieldCheck;

    protected static string $relationship = 'agentBridgeConfirmations';

    protected static bool $shouldSkipAuthorization = true;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-agent-bridge::admin.confirmations');
    }

    /**
     * @param  Builder<CapellAgentBridgeConfirmation>  $query
     * @return Builder<CapellAgentBridgeConfirmation>
     */
    public static function scopedQueryForUser(Builder $query, Model $user): Builder
    {
        return $query
            ->where('user_type', $user->getMorphClass())
            ->where('user_id', $user->getKey());
    }

    public function getRelationship(): Relation|Builder
    {
        return self::scopedQueryForUser(CapellAgentBridgeConfirmation::query(), $this->ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('capability_key')
                    ->label(__('capell-agent-bridge::admin.capability'))
                    ->searchable(),
                TextColumn::make('scope')
                    ->label(__('capell-agent-bridge::admin.scope')),
                TextColumn::make('used_at')
                    ->label(__('capell-agent-bridge::admin.used_at'))
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
