<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Data;

use Spatie\LaravelData\Data;

class PasswordPolicyStatusData extends Data
{
    public function __construct(
        public bool $mustChangePassword,
        public bool $passwordExpired,
        public ?string $reason = null,
    ) {}

    public function shouldRedirect(): bool
    {
        return $this->mustChangePassword || $this->passwordExpired;
    }
}
