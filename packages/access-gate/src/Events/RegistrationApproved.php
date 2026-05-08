<?php

declare(strict_types=1);

namespace Capell\AccessGate\Events;

use Capell\AccessGate\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;

final class RegistrationApproved
{
    use Dispatchable;

    public function __construct(
        public readonly Registration $registration,
    ) {}
}
