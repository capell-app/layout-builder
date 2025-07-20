<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Models\Article;

enum BlogModelEnum: string
{
    case Article = Article::class;
}
