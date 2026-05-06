<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use JsonException;
use Spatie\LaravelData\Data;

/**
 * @implements CastsAttributes<Data|null, mixed>
 */
class EncryptedDataCast implements CastsAttributes
{
    /**
     * @param  class-string<Data>  $dataClass
     */
    public function __construct(
        private string $dataClass,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Data
    {
        if ($value === null || $value === '') {
            return null;
        }

        $payload = is_array($value)
            ? $value
            : $this->decodeStoredPayload($this->decryptStoredValue($value));

        $dataClass = $this->dataClass;

        return $dataClass::from($payload);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($this->encodePayload($this->normalizePayload($value)));
    }

    private function decryptStoredValue(mixed $value): string
    {
        $storedValue = $this->normalizeStoredValue($value);

        try {
            return Crypt::decryptString($storedValue);
        } catch (DecryptException) {
            return $storedValue;
        }
    }

    private function normalizeStoredValue(mixed $value): string
    {
        if (! is_string($value)) {
            return $this->encodePayload($this->normalizePayload($value));
        }

        $decodedValue = json_decode($value, associative: true);

        if (is_string($decodedValue)) {
            return $decodedValue;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeStoredPayload(string $payload): array
    {
        $decodedPayload = json_decode($payload, associative: true);

        if (is_array($decodedPayload)) {
            return $decodedPayload;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(mixed $value): array
    {
        if ($value instanceof Data) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $this->decodeStoredPayload($value);
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodePayload(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{}';
        }
    }
}
