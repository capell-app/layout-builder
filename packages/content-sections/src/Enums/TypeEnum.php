<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\ContentSections\Models\Section;

enum TypeEnum: string
{
    case Section = 'section';

    public function getModel(): string
    {
        return match ($this) {
            self::Section => Section::class,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Section => __('capell-content-sections::generic.content'),
        };
    }
}
