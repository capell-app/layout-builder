<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum TagTypeEnum: string
{
    case CONTENT = 'content';

    case PAGE = 'page';
}
