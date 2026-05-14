<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutBreakpoint: string
{
    case Desktop = 'desktop';
    case Tablet = 'tablet';
    case Mobile = 'mobile';

    public static function fromNullable(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::from($value);
    }

    public function maxCanvasWidth(): string
    {
        return match ($this) {
            self::Desktop => '100%',
            self::Tablet => '768px',
            self::Mobile => '390px',
        };
    }
}
