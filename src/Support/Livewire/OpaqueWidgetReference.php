<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Livewire;

use Illuminate\Support\Facades\Crypt;
use Throwable;

final class OpaqueWidgetReference
{
    /**
     * @param  array<string, mixed>  $reference
     */
    public static function encode(array $reference): string
    {
        return Crypt::encryptString(json_encode($reference, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(string $reference): array
    {
        try {
            $decoded = json_decode(Crypt::decryptString($reference), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
