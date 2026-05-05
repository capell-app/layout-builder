<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Actions;

use Capell\PasswordSecurity\Support\PasswordSecuritySettingsResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

class RecordPasswordHistoryAction
{
    use AsObject;

    public function handle(Model $user, string $passwordHash): void
    {
        $settings = resolve(PasswordSecuritySettingsResolver::class)->settings();

        if (! $settings->passwordHistoryEnabled || ! Schema::hasTable('password_security_password_histories')) {
            return;
        }

        DB::table('password_security_password_histories')->insert([
            'user_id' => $user->getKey(),
            'password' => $passwordHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
