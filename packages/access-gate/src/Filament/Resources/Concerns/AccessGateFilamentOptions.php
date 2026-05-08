<?php

declare(strict_types=1);

namespace Capell\AccessGate\Filament\Resources\Concerns;

use BackedEnum;

trait AccessGateFilamentOptions
{
    /**
     * @param  class-string<BackedEnum>  $enum
     * @return array<string, string>
     */
    protected static function enumOptions(string $enum, string $translationPrefix): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn (BackedEnum $case): array => [
                (string) $case->value => __(sprintf('%s.%s', $translationPrefix, $case->value)),
            ])
            ->all();
    }
}
