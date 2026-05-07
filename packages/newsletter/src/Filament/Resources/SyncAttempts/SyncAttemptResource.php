<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\SyncAttempts;

use BackedEnum;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Filament\Resources\SyncAttempts\Pages\ListSyncAttempts;
use Capell\Newsletter\Models\SyncAttempt;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SyncAttemptResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('operation')->label(__('capell-newsletter::table.operation'))->searchable(),
                TextColumn::make('sync_status')->label(__('capell-newsletter::table.sync_status'))->badge()->sortable(),
                TextColumn::make('attempts')->label(__('capell-newsletter::table.attempts'))->sortable(),
                TextColumn::make('error_message')->limit(80)->wrap(),
                TextColumn::make('last_attempted_at')->label(__('capell-newsletter::table.last_attempted_at'))->dateTime()->sortable(),
            ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return SyncAttempt::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('subscriber', function (Builder $subscriberQuery): void {
                        SiteScope::applyForCurrentActor($subscriberQuery);
                    })
                    ->orWhereHas('providerConnection', function (Builder $connectionQuery): void {
                        SiteScope::applyForCurrentActor($connectionQuery);
                    });
            });
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.sync_attempts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncAttempts::route('/'),
        ];
    }
}
