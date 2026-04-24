<?php

declare(strict_types=1);

namespace Capell\Tests\Support\Concerns;

use Illuminate\Database\Eloquent\Model;

trait CreatesAdminUser
{
    public function createUser(): Model {}

    public function actingAs(Model $user): self {}

    public function actingAsAdmin(): self {}
}
