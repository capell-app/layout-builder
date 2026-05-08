<?php

declare(strict_types=1);

namespace Capell\AccessGate\Filament\Resources\Grants;

use BackedEnum;
use Capell\AccessGate\Actions\RevokeAccessGateGrantAction;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Filament\Resources\Concerns\AccessGateFilamentOptions;
use Capell\AccessGate\Filament\Resources\Grants\Pages\ListGrants;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Providers\AccessGateServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class GrantResource extends Resource
{
    use AccessGateFilamentOptions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Key;

    protected static ?string $recordTitleAttribute = 'email';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['area', 'registration'])->latest('updated_at'))
            ->columns([
                TextColumn::make('area.key')
                    ->label(__('capell-access-gate::filament.fields.area'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label(__('capell-access-gate::filament.fields.subject_type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('capell-access-gate::filament.fields.email'))
                    ->searchable(),
                TextColumn::make('subject_id')
                    ->label(__('capell-access-gate::filament.fields.subject_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('capell-access-gate::filament.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label(__('capell-access-gate::filament.fields.starts_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('capell-access-gate::filament.fields.expires_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->label(__('capell-access-gate::filament.fields.revoked_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('access_area_id')
                    ->label(__('capell-access-gate::filament.fields.area'))
                    ->relationship('area', 'key'),
                SelectFilter::make('status')
                    ->label(__('capell-access-gate::filament.fields.status'))
                    ->options(self::enumOptions(GrantStatus::class, 'capell-access-gate::filament.grant_status')),
                SelectFilter::make('subject_type')
                    ->label(__('capell-access-gate::filament.fields.subject_type'))
                    ->options(self::enumOptions(GrantSubjectType::class, 'capell-access-gate::filament.grant_subject_type')),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('capell-access-gate::filament.actions.revoke'))
                    ->color('danger')
                    ->visible(fn (Grant $record): bool => $record->status === GrantStatus::Active)
                    ->requiresConfirmation()
                    ->action(fn (Grant $record): mixed => RevokeAccessGateGrantAction::run($record, revokedByUserId: auth()->id())),
            ]);
    }

    /** @return class-string<Grant> */
    #[Override]
    public static function getModel(): string
    {
        return Grant::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-access-gate::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-access-gate::filament.resources.grants');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(AccessGateServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrants::route('/'),
        ];
    }
}
