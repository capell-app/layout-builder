<?php

declare(strict_types=1);

namespace Capell\AccessGate\Contracts;

use Capell\AccessGate\Data\RegistrationFieldValue;

interface RegistrationField
{
    public function key(): string;

    public function label(): string;

    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(array $input): RegistrationFieldValue;
}
