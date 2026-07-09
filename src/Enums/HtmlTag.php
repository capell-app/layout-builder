<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum HtmlTag: string implements HasLabel
{
    case Div = 'div';
    case Section = 'section';
    case Article = 'article';
    case Aside = 'aside';
    case Header = 'header';
    case Footer = 'footer';
    case Navigation = 'nav';
    case Main = 'main';

    public function getLabel(): string
    {
        return (string) __('capell-layout-builder::form.tag_' . ($this === self::Navigation ? 'nav' : $this->value));
    }
}
