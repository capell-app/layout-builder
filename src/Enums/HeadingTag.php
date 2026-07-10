<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum HeadingTag: string implements HasLabel
{
    case H1 = 'h1';
    case H2 = 'h2';
    case H3 = 'h3';
    case H4 = 'h4';
    case H5 = 'h5';
    case H6 = 'h6';
    case Div = 'div';
    case Paragraph = 'p';

    public function getLabel(): string
    {
        return $this->value;
    }
}
