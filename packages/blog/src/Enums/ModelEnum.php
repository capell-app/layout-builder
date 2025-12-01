<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;

enum ModelEnum: string
{
    case Article = Article::class;

    case Tag = Tag::class;
}
