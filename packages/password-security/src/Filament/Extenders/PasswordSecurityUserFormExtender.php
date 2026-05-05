<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\UserFormExtender;
use Capell\PasswordSecurity\Actions\RecordPasswordHistoryAction;
use Capell\PasswordSecurity\Actions\ValidatePasswordChangeAction;
use Capell\PasswordSecurity\Data\PasswordChangeData;
use Capell\PasswordSecurity\Support\PasswordSecuritySettingsResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PasswordSecurityUserFormExtender implements UserFormExtender
{
    public function mutateDataBeforeCreate(array $data): array
    {
        if ($this->hasNoPassword($data)) {
            return $data;
        }

        $settings = resolve(PasswordSecuritySettingsResolver::class)->settings();

        ValidatePasswordChangeAction::run(
            null,
            $this->passwordChangeData($data),
            $settings->compromisedPasswordChecksEnabled,
        );

        return $data;
    }

    public function afterCreate(Model $record): void
    {
        $this->persistPasswordSecurityAttributes($record);
    }

    public function mutateDataBeforeSave(Model $record, array $data): array
    {
        if ($this->hasNoPassword($data)) {
            return $data;
        }

        $settings = resolve(PasswordSecuritySettingsResolver::class)->settings();

        ValidatePasswordChangeAction::run(
            $record,
            $this->passwordChangeData($data),
            $settings->compromisedPasswordChecksEnabled,
        );

        RecordPasswordHistoryAction::run($record, (string) $record->getAttribute('password'));

        return $data;
    }

    public function afterSave(Model $record): void
    {
        if (! $record->wasChanged('password')) {
            return;
        }

        $this->persistPasswordSecurityAttributes($record);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasNoPassword(array $data): bool
    {
        return ! array_key_exists('password', $data) || blank($data['password']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function passwordChangeData(array $data): PasswordChangeData
    {
        $password = (string) $data['password'];

        return new PasswordChangeData(
            password: $password,
            passwordConfirmation: $password,
            requireCurrentPassword: false,
        );
    }

    private function persistPasswordSecurityAttributes(Model $record): void
    {
        $values = [];

        if (Schema::hasColumn($record->getTable(), 'password_changed_at')) {
            $values['password_changed_at'] = now();
        }

        if (Schema::hasColumn($record->getTable(), 'must_change_password')) {
            $values['must_change_password'] = false;
        }

        if ($values === []) {
            return;
        }

        $record->forceFill($values)->save();
    }
}
