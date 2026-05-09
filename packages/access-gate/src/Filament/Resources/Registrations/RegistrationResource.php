<?php

declare(strict_types=1);

namespace Capell\AccessGate\Filament\Resources\Registrations;

use App\Actions\AccessGate\InviteApprovedRegistrationToCoreRepositoriesAction;
use App\Enums\GitHubRepositoryAccessStatus;
use BackedEnum;
use Capell\AccessGate\Actions\ApproveRegistrationAction;
use Capell\AccessGate\Actions\ExpireRegistrationAction;
use Capell\AccessGate\Actions\RejectRegistrationAction;
use Capell\AccessGate\Actions\ResendAccessGateClaimTokenAction;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Filament\Resources\Concerns\AccessGateFilamentOptions;
use Capell\AccessGate\Filament\Resources\Registrations\Pages\ListRegistrations;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Providers\AccessGateServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class RegistrationResource extends Resource
{
    use AccessGateFilamentOptions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Envelope;

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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['area'])->latest('requested_at'))
            ->columns([
                TextColumn::make('email')
                    ->label(__('capell-access-gate::filament.fields.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area.key')
                    ->label(__('capell-access-gate::filament.fields.area'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('capell-access-gate::filament.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('github_repository_access_status')
                    ->label(__('capell-access-gate::filament.fields.github_repository_access_status'))
                    ->state(fn (Registration $record): string => self::githubRepositoryAccessStatusLabel($record))
                    ->badge()
                    ->color(fn (Registration $record): string => self::githubRepositoryAccessStatusColor($record))
                    ->toggleable(),
                TextColumn::make('requested_host')
                    ->label(__('capell-access-gate::filament.fields.requested_host'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('capell-access-gate::filament.fields.position'))
                    ->sortable(),
                TextColumn::make('requested_at')
                    ->label(__('capell-access-gate::filament.fields.requested_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('capell-access-gate::filament.fields.approved_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('claimed_at')
                    ->label(__('capell-access-gate::filament.fields.claimed_at'))
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
                    ->options(self::enumOptions(RegistrationStatus::class, 'capell-access-gate::filament.registration_status')),
                SelectFilter::make('requested_host')
                    ->label(__('capell-access-gate::filament.fields.requested_host'))
                    ->options(fn (): array => Registration::query()
                        ->whereNotNull('requested_host')
                        ->distinct()
                        ->pluck('requested_host', 'requested_host')
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label(__('capell-access-gate::filament.actions.approve'))
                        ->visible(fn (Registration $record): bool => $record->status === RegistrationStatus::Pending)
                        ->action(fn (Registration $record): mixed => ApproveRegistrationAction::run($record, approvedByUserId: auth()->id())),
                    Action::make('reject')
                        ->label(__('capell-access-gate::filament.actions.reject'))
                        ->color('danger')
                        ->visible(fn (Registration $record): bool => $record->status === RegistrationStatus::Pending)
                        ->action(fn (Registration $record): mixed => RejectRegistrationAction::run($record, rejectedByUserId: auth()->id())),
                    Action::make('resendClaim')
                        ->label(__('capell-access-gate::filament.actions.resend_claim'))
                        ->visible(fn (Registration $record): bool => in_array($record->status, [RegistrationStatus::Approved, RegistrationStatus::Claimed], true))
                        ->action(fn (Registration $record): mixed => ResendAccessGateClaimTokenAction::run($record)),
                    Action::make('retryGithubInvites')
                        ->label(__('capell-access-gate::filament.actions.retry_github_invites'))
                        ->icon(Heroicon::ArrowPath)
                        ->visible(fn (Registration $record): bool => self::canRetryGithubInvites($record))
                        ->requiresConfirmation()
                        ->action(fn (Registration $record): mixed => self::retryGithubInvites($record)),
                    Action::make('expire')
                        ->label(__('capell-access-gate::filament.actions.expire'))
                        ->color('danger')
                        ->visible(fn (Registration $record): bool => ! in_array($record->status, [RegistrationStatus::Claimed, RegistrationStatus::Expired], true))
                        ->action(fn (Registration $record): mixed => ExpireRegistrationAction::run($record, expiredByUserId: auth()->id())),
                ]),
            ]);
    }

    /** @return class-string<Registration> */
    #[Override]
    public static function getModel(): string
    {
        return Registration::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-access-gate::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-access-gate::filament.resources.registrations');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(AccessGateServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrations::route('/'),
        ];
    }

    private static function githubRepositoryAccessStatusLabel(Registration $registration): string
    {
        $status = self::githubRepositoryAccessStatus($registration);

        if (is_object($status) && method_exists($status, 'label')) {
            return (string) $status->label();
        }

        return __('capell-access-gate::filament.fields.status');
    }

    private static function githubRepositoryAccessStatusColor(Registration $registration): string
    {
        $status = self::githubRepositoryAccessStatus($registration);

        if (is_object($status) && method_exists($status, 'color')) {
            return (string) $status->color();
        }

        return 'gray';
    }

    private static function canRetryGithubInvites(Registration $registration): bool
    {
        if (! class_exists(InviteApprovedRegistrationToCoreRepositoriesAction::class)) {
            return false;
        }

        $status = self::githubRepositoryAccessStatus($registration);
        $hasGithubUsername = is_string($registration->field_values['github_username']['value'] ?? null)
            && $registration->field_values['github_username']['value'] !== '';

        return $hasGithubUsername
            && is_object($status)
            && method_exists($status, 'isRetryable')
            && $status->isRetryable()
            && in_array($registration->status, [RegistrationStatus::Approved, RegistrationStatus::Claimed], true);
    }

    private static function retryGithubInvites(Registration $registration): void
    {
        if (! class_exists(InviteApprovedRegistrationToCoreRepositoriesAction::class)) {
            Notification::make()
                ->title(__('capell-access-gate::filament.messages.github_invites_unavailable'))
                ->danger()
                ->send();

            return;
        }

        resolve(InviteApprovedRegistrationToCoreRepositoriesAction::class)->handle($registration);

        Notification::make()
            ->title(__('capell-access-gate::filament.messages.github_invites_retried'))
            ->success()
            ->send();
    }

    private static function githubRepositoryAccessStatus(Registration $registration): ?object
    {
        if (! class_exists(GitHubRepositoryAccessStatus::class)) {
            return null;
        }

        return GitHubRepositoryAccessStatus::forRegistration($registration);
    }
}
