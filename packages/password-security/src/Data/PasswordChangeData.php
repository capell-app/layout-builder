<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Data;

use Spatie\LaravelData\Data;

class PasswordChangeData extends Data
{
    public function __construct(
        public string $password,
        public string $passwordConfirmation,
        public ?string $currentPassword = null,
        public bool $requireCurrentPassword = true,
    ) {}
}
