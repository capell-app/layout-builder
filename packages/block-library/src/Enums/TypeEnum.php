<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Capell\BlockLibrary\Models\ContentBlock;

enum TypeEnum: string
{
    case ContentBlock = 'content_block';

    public function getModel(): string
    {
        return match ($this) {
            self::ContentBlock => ContentBlock::class,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ContentBlock => __('capell-block-library::generic.content'),
        };
    }
}
