<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum BlogPageTypeEnum: string
{
    case Archive = 'archive';
    case Blog = 'blog';
    case Tag = 'tag';
}
