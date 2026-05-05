<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Actions;

use Capell\PasswordSecurity\Data\PasswordChangeData;
use Capell\PasswordSecurity\Support\PasswordSecuritySettingsResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

class UpdatePasswordAction
{
    use AsObject;

    public function handle(Model $user, PasswordChangeData $input): void
    {
        $settings = resolve(PasswordSecuritySettingsResolver::class)->settings();

        ValidatePasswordChangeAction::run($user, $input, $settings->compromisedPasswordChecksEnabled);

        DB::transaction(function () use ($user, $input): void {
            $currentPasswordHash = (string) $user->getAttribute('password');
            RecordPasswordHistoryAction::run($user, $currentPasswordHash);

            $values = ['password' => Hash::make($input->password)];

            if (Schema::hasColumn($user->getTable(), 'password_changed_at')) {
                $values['password_changed_at'] = now();
            }

            if (Schema::hasColumn($user->getTable(), 'must_change_password')) {
                $values['must_change_password'] = false;
            }

            $user->forceFill($values)->save();
        });
    }
}
