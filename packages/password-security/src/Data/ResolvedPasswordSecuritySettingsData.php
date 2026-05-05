<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Data;

use Spatie\LaravelData\Data;

class ResolvedPasswordSecuritySettingsData extends Data
{
    public function __construct(
        public bool $passwordExpiryEnabled = false,
        public int $passwordExpiryDays = 90,
        public bool $forceChangeEnabled = false,
        public bool $compromisedPasswordChecksEnabled = false,
        public bool $passwordHistoryEnabled = false,
        public int $passwordHistoryCount = 5,
    ) {}
}
