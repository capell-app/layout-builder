<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\LayoutBuilder\Contracts\WidgetSnapshots\WidgetSnapshotLocatorCipher;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use RuntimeException;

final class CurrentWidgetSnapshotLocatorCipher implements WidgetSnapshotLocatorCipher
{
    private readonly Encrypter $encrypter;

    public function __construct(?string $key = null, ?string $cipher = null)
    {
        $configuredKey = $key ?? config('app.key');
        $configuredCipher = $cipher ?? config('app.cipher', 'AES-256-CBC');
        if (! is_string($configuredKey) || $configuredKey === '' || ! is_string($configuredCipher)) {
            throw new RuntimeException('A current application encryption key is required for widget snapshot locators.');
        }

        $rawKey = Str::startsWith($configuredKey, 'base64:')
            ? base64_decode(Str::after($configuredKey, 'base64:'), true)
            : $configuredKey;
        if (! is_string($rawKey)) {
            throw new RuntimeException('The current application encryption key is invalid.');
        }

        // Deliberately do not configure previousKeys(). Locator revocation on
        // key rotation is a security property, unlike general app decryption.
        $this->encrypter = new Encrypter($rawKey, $configuredCipher);
    }

    public function encrypt(string $plaintext): string
    {
        return $this->encrypter->encryptString($plaintext);
    }

    public function decrypt(string $ciphertext): string
    {
        return $this->encrypter->decryptString($ciphertext);
    }
}
