<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Actions;

use Capell\PasswordPolicy\Data\PasswordChangeData;
use Capell\PasswordPolicy\Support\PasswordPolicySettingsResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

class ValidatePasswordChangeAction
{
    use AsObject;

    public function handle(?Model $user, PasswordChangeData $input, bool $checkCompromisedPasswords): void
    {
        if (
            $user instanceof Model
            && $input->requireCurrentPassword
            && ! Hash::check((string) $input->currentPassword, (string) $user->getAttribute('password'))
        ) {
            throw ValidationException::withMessages([
                'current_password' => __('capell-password-policy::validation.current_password'),
            ]);
        }

        $passwordRule = Password::min(8);

        if ($checkCompromisedPasswords) {
            $passwordRule->uncompromised();
        }

        Validator::make([
            'password' => $input->password,
            'password_confirmation' => $input->passwordConfirmation,
        ], [
            'password' => ['required', 'confirmed', $passwordRule],
        ])->validate();

        if ($user instanceof Model) {
            $this->ensurePasswordHasNotBeenUsed($user, $input->password);
        }
    }

    private function ensurePasswordHasNotBeenUsed(Model $user, string $password): void
    {
        $settings = resolve(PasswordPolicySettingsResolver::class)->settings();

        if (! $settings->passwordHistoryEnabled) {
            return;
        }

        $passwordHashes = collect([(string) $user->getAttribute('password')]);

        if (Schema::hasTable('password_policy_password_histories')) {
            $historyHashes = DB::table('password_policy_password_histories')
                ->where('user_id', $user->getKey())
                ->latest('id')
                ->limit(max(1, $settings->passwordHistoryCount))
                ->pluck('password');

            $passwordHashes = $passwordHashes->merge($historyHashes);
        }

        foreach ($passwordHashes as $passwordHash) {
            if (Hash::check($password, $passwordHash)) {
                throw ValidationException::withMessages([
                    'password' => __('capell-password-policy::validation.password_reused'),
                ]);
            }
        }
    }
}
