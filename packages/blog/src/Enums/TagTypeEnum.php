<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum TagTypeEnum: string
{
    case Article = 'article';
    case Content = 'content';
    case Page = 'page';
}
