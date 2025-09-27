<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

use Capell\Blog\Filament\Resources\Articles\ArticleResource;

enum BlogResourceEnum: string
{
    case Article = 'article';

    public function getResource(): string
    {
        return match ($this) {
            self::Article => ArticleResource::class,
        };
    }
}
