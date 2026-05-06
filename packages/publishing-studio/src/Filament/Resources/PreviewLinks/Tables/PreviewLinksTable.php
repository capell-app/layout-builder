<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PreviewLinks\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\PublishingStudio\Actions\ExtendPreviewLinkAction;
use Capell\PublishingStudio\Actions\RevokePreviewLinkAction;
use Capell\PublishingStudio\Models\PreviewLink;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PreviewLinksTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(static::getTableColumns())
            ->recordActions([
                Action::make('revoke')
                    ->label(__('capell-admin::workspace.preview_link.actions.revoke'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('capell-admin::workspace.preview_link.actions.revoke'))
                    ->modalDescription(__('capell-admin::workspace.preview_link.actions.revoke_confirm'))
                    ->disabled(fn (PreviewLink $record): bool => $record->isRevoked() || $record->isExpired())
                    ->action(function (PreviewLink $record): void {
                        (new RevokePreviewLinkAction)->handle($record, auth()->user());

                        Notification::make()
                            ->title(__('capell-admin::workspace.preview_link.notifications.revoked'))
                            ->success()
                            ->send();
                    }),

                Action::make('extend')
                    ->label(__('capell-admin::workspace.preview_link.actions.extend_24h'))
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('capell-admin::workspace.preview_link.actions.extend_24h'))
                    ->modalDescription(__('capell-admin::workspace.preview_link.actions.extend_confirm'))
                    ->disabled(fn (PreviewLink $record): bool => $record->isRevoked())
                    ->action(function (PreviewLink $record): void {
                        (new ExtendPreviewLinkAction)->handle($record, 1440, auth()->user());

                        Notification::make()
                            ->title(__('capell-admin::workspace.preview_link.notifications.extended'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),

            TextColumn::make('token')
                ->label(__('capell-admin::workspace.preview_link.token'))
                ->formatStateUsing(fn (string $state): string => substr($state, 0, 12) . '…')
                ->tooltip(fn (PreviewLink $record): string => $record->token)
                ->copyable()
                ->copyMessage(__('capell-admin::workspace.preview_link.token')),

            TextColumn::make('workspace.name')
                ->label(__('capell-admin::workspace.preview_link.workspace'))
                ->placeholder('—')
                ->searchable(),

            TextColumn::make('issuedBy.name')
                ->label(__('capell-admin::workspace.preview_link.issued_by'))
                ->placeholder('—'),

            DateColumn::make('issued_at')
                ->label(__('capell-admin::workspace.preview_link.issued_at')),

            DateColumn::make('expires_at')
                ->label(__('capell-admin::workspace.preview_link.expires_at')),

            TextColumn::make('access_count')
                ->label(__('capell-admin::workspace.preview_link.access_count'))
                ->numeric()
                ->sortable(),

            TextColumn::make('status')
                ->label(__('capell-admin::table.status'))
                ->badge()
                ->state(function (PreviewLink $record): string {
                    if ($record->isRevoked()) {
                        return (string) __('capell-admin::workspace.preview_link.status_revoked');
                    }

                    if ($record->isExpired()) {
                        return (string) __('capell-admin::workspace.preview_link.status_expired');
                    }

                    return (string) __('capell-admin::workspace.preview_link.status_active');
                })
                ->color(function (PreviewLink $record): string {
                    if ($record->isRevoked()) {
                        return 'danger';
                    }

                    if ($record->isExpired()) {
                        return 'warning';
                    }

                    return 'success';
                }),
        ];
    }
}
