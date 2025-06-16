<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Models;

enum BlogModelEnum: string
{
    case Article = Models\Article::class;
}
