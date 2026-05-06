<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\ContentSections\Models\Section;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case Section = 'section';

    public function getModel(): string
    {
        return match ($this) {
            self::Section => Section::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::Section => 'sections',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Section => 'Section',
        };
    }
}
