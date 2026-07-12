<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImageGalleryColumnCount: int implements HasLabel
{
    case One = 1;
    case Two = 2;
    case Three = 3;
    case Four = 4;

    public function getLabel(): string
    {
        return (string) $this->value;
    }
}
