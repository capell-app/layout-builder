<?php

declare(strict_types=1);

namespace Capell\AccessGate\Support;

use Capell\AccessGate\Contracts\RegistrationField;
use InvalidArgumentException;

final class RegistrationFieldRegistry
{
    /** @var array<string, RegistrationField|class-string<RegistrationField>> */
    private array $fields = [];

    /**
     * @param  RegistrationField|class-string<RegistrationField>  $field
     */
    public function register(RegistrationField|string $field): void
    {
        $resolvedField = is_string($field) ? app($field) : $field;

        if (! $resolvedField instanceof RegistrationField) {
            throw new InvalidArgumentException('Access gate registration fields must implement RegistrationField.');
        }

        $this->fields[$resolvedField->key()] = $field;
    }

    /**
     * @return array<string, RegistrationField>
     */
    public function all(): array
    {
        return collect($this->fields)
            ->mapWithKeys(function (RegistrationField|string $field): array {
                $resolvedField = is_string($field) ? app($field) : $field;

                return [$resolvedField->key() => $resolvedField];
            })
            ->all();
    }
}
