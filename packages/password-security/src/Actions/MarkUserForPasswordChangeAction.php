<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

class MarkUserForPasswordChangeAction
{
    use AsObject;

    public function handle(Model $user): void
    {
        if (! Schema::hasColumn($user->getTable(), 'must_change_password')) {
            return;
        }

        $user->forceFill(['must_change_password' => true])->save();
    }
}
