<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Filament\Resources\Tags\TagResource;

enum ResourceEnum: string
{
    case Article = ArticleResource::class;
    case Tag = TagResource::class;
}
