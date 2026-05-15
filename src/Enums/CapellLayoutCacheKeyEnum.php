<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum CapellLayoutCacheKeyEnum: string
{
    case ElementByKey = 'capell_layout_element_by_key:';

    case ElementOptions = 'capell_layout_element_options:';
}
