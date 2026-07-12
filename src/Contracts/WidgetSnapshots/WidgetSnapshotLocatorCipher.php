<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\WidgetSnapshots;

interface WidgetSnapshotLocatorCipher
{
    public function encrypt(string $plaintext): string;

    public function decrypt(string $ciphertext): string;
}
