<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Filament\Extenders;

use Capell\PasswordPolicy\Actions\MarkUserForPasswordChangeAction;
use Capell\PasswordPolicy\Support\PasswordPolicySettingsResolver;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PasswordPolicyUserTableExtender
{
    public function columns(): array
    {
        if (! $this->hasPasswordPolicyColumns()) {
            return [];
        }

        return [
            IconColumn::make('must_change_password')
                ->label(__('capell-password-policy::users.must_change_password'))
                ->boolean()
                ->toggleable(),
            TextColumn::make('password_changed_at')
                ->label(__('capell-password-policy::users.password_changed_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public function filters(): array
    {
        if (! $this->hasPasswordPolicyColumns()) {
            return [];
        }

        return [
            TernaryFilter::make('must_change_password')
                ->label(__('capell-password-policy::users.must_change_password')),
            Filter::make('password_policy_expired')
                ->label(__('capell-password-policy::users.password_expired'))
                ->query(fn (Builder $query): Builder => $this->expiredPasswordQuery($query)),
            Filter::make('password_policy_missing_password_changed_at')
                ->label(__('capell-password-policy::users.password_never_changed'))
                ->query(fn (Builder $query): Builder => $query->whereNull('password_changed_at')),
        ];
    }

    public function recordActions(): array
    {
        if (! $this->canRequirePasswordChange()) {
            return [];
        }

        return [
            Action::make('require_password_change')
                ->label(__('capell-password-policy::users.require_password_change'))
                ->icon(Heroicon::OutlinedKey)
                ->action(fn (Model $record): null => $this->markUser($record)),
        ];
    }

    public function toolbarActions(): array
    {
        if (! $this->canRequirePasswordChange()) {
            return [];
        }

        return [
            BulkAction::make('require_password_change')
                ->label(__('capell-password-policy::users.require_password_change'))
                ->icon(Heroicon::OutlinedKey)
                ->action(function (Collection $records): void {
                    $records->each(fn (Model $record): null => $this->markUser($record));
                }),
        ];
    }

    private function hasPasswordPolicyColumns(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'must_change_password')
            && Schema::hasColumn('users', 'password_changed_at');
    }

    private function canRequirePasswordChange(): bool
    {
        $settings = resolve(PasswordPolicySettingsResolver::class)->settings();

        return $this->hasPasswordPolicyColumns() && $settings->forceChangeEnabled;
    }

    private function markUser(Model $record): null
    {
        MarkUserForPasswordChangeAction::run($record);

        return null;
    }

    private function expiredPasswordQuery(Builder $query): Builder
    {
        $settings = resolve(PasswordPolicySettingsResolver::class)->settings();

        return $query->where(function (Builder $passwordQuery) use ($settings): void {
            $passwordQuery
                ->whereNull('password_changed_at')
                ->orWhere('password_changed_at', '<=', now()->subDays(max(1, $settings->passwordExpiryDays)));
        });
    }
}
