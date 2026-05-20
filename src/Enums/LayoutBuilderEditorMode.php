<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutBuilderEditorMode: string
{
    case ContentFirst = 'content_first';
    case LayoutFirst = 'layout_first';

    public static function fromConfig(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom((string) $value) ?? self::ContentFirst;
    }
}
