<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutPreviewStatusEnum: string
{
    case Pending = 'pending';

    case Ready = 'ready';

    case Failed = 'failed';
}
