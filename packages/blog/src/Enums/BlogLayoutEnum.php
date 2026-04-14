<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum BlogLayoutEnum: string
{
    case Archives = 'archives';
    case Article = 'article';
    case BlogPage = 'blog-results';
    case Tags = 'tags';
}
