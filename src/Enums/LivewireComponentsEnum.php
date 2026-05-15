<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LivewireComponentsEnum: string
{
    case LayoutBuilder = 'capell-layout-builder::filament.layout-builder';

    case PageAssetsTable = 'capell-layout-builder::assets.table.page-assets';

    case PagesElement = 'capell.element.pages';
}
