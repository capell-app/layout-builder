<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum CapellLayoutCacheKeyEnum: string
{
    case BlockByKey = 'capell_layout_block_by_key:';

    case BlockOptions = 'capell_layout_block_options:';
}
