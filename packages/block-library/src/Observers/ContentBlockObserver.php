<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Observers;

use Capell\BlockLibrary\Enums\LayoutTypeEnum;
use Capell\BlockLibrary\Models\ContentBlock;
use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Type;
use Capell\Core\Support\CapellCoreHelper;
use InvalidArgumentException;

class ContentBlockObserver
{
    public function creating(ContentBlock $content_block): void
    {
        if (! $content_block->type_id) {
            $content_block->type_id = Type::query()->where('type', LayoutTypeEnum::ContentBlock)->default()->value('id');
            throw_unless($content_block->type_id, InvalidArgumentException::class, 'Unable to create content without a type.');
        }

        // Normalize parent_id from loaded relation if needed (nested set).
        if ($content_block->parent_id !== null) {
            $parent = $content_block->getRelationValue('parent');
            if ($parent !== null && $content_block->parent_id !== $parent->id) {
                $content_block->parent_id = $parent->id;
            }
        }
    }

    public function saving(ContentBlock $content_block): void
    {
        if (method_exists($content_block, 'nodeCallPendingAction')) {
            $content_block->nodeCallPendingAction();
        }
    }

    public function deleting(ContentBlock $content_block): void
    {
        if (method_exists($content_block, 'nodeRefreshNode')) {
            $content_block->nodeRefreshNode();
        }
    }

    public function deleted(ContentBlock $content_block): void
    {
        if (method_exists($content_block, 'nodeDeleteDescendants')) {
            $content_block->nodeDeleteDescendants();
        }

        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }

    public function restoring(ContentBlock $content_block): void {}

    public function restored(ContentBlock $content_block): void
    {
        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }
}
