<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\LayoutBuilder\Data\WidgetSnapshots\WidgetSnapshotLocatorData;
use Illuminate\Contracts\Encryption\StringEncrypter;
use InvalidArgumentException;
use Throwable;

final readonly class WidgetSnapshotLocatorCodec
{
    public const int VERSION = 1;

    public const string PURPOSE = 'public-widget-snapshot';

    private const int MAX_ENCODED_BYTES = 2048;

    private const int MAX_DECODED_BYTES = 1024;

    public function __construct(private StringEncrypter $encrypter) {}

    public function encode(WidgetSnapshotLocatorData $data): string
    {
        if ($data->version !== self::VERSION || $data->purpose !== self::PURPOSE) {
            throw new InvalidArgumentException('Invalid widget snapshot locator contract.');
        }

        $json = json_encode($data->toArray(), JSON_THROW_ON_ERROR);
        if (strlen($json) > self::MAX_DECODED_BYTES) {
            throw new InvalidArgumentException('Widget snapshot locator is too large.');
        }

        $encoded = 'v1.' . rtrim(strtr(base64_encode($this->encrypter->encryptString($json)), '+/', '-_'), '=');
        if (strlen($encoded) > self::MAX_ENCODED_BYTES) {
            throw new InvalidArgumentException('Widget snapshot locator is too large.');
        }

        return $encoded;
    }

    public function decode(string $locator): ?WidgetSnapshotLocatorData
    {
        if (strlen($locator) > self::MAX_ENCODED_BYTES || ! str_starts_with($locator, 'v1.')) {
            return null;
        }

        try {
            $encoded = substr($locator, 3);
            $padding = (4 - strlen($encoded) % 4) % 4;
            $ciphertext = base64_decode(strtr($encoded . str_repeat('=', $padding), '-_', '+/'), true);
            if (! is_string($ciphertext) || strlen($ciphertext) > self::MAX_DECODED_BYTES * 4) {
                return null;
            }
            $canonical = rtrim(strtr(base64_encode($ciphertext), '+/', '-_'), '=');
            if (! hash_equals($canonical, $encoded)) {
                return null;
            }

            $json = $this->encrypter->decryptString($ciphertext);
            if (strlen($json) > self::MAX_DECODED_BYTES) {
                return null;
            }

            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                return null;
            }

            $data = WidgetSnapshotLocatorData::from($payload);

            return $data->version === self::VERSION
                && $data->purpose === self::PURPOSE
                && $data->snapshotId > 0
                && $data->pageableId > 0
                && $data->pageableType !== ''
                && $data->targetInstanceId !== ''
                    ? $data
                    : null;
        } catch (Throwable) {
            return null;
        }
    }
}
