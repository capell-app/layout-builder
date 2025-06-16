<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Filament\Resources\ArticleResource;

enum BlogResourceEnum: string
{
    case Article = ArticleResource::class;
}
