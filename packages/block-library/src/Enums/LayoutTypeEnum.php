<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Capell\BlockLibrary\Filament\Resources\BlockLibrary\ContentBlockResource;
use Capell\BlockLibrary\Models\ContentBlock;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case ContentBlock = 'content_block';

    public function getResource(): string
    {
        return match ($this) {
            self::ContentBlock => ContentBlockResource::class,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::ContentBlock => ContentBlock::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::ContentBlock => 'block_library',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ContentBlock => 'Content block',
        };
    }

    public function getCreatorClass(): ?string
    {
        return null;
    }
}
