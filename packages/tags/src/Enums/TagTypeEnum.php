<?php

declare(strict_types=1);

namespace Capell\Tags\Enums;

enum TagTypeEnum: string
{
    case Article = 'article';
    case Content = 'content';
    case Page = 'page';
}
