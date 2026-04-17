<?php

declare(strict_types=1);

namespace Capell\Mosaic\Observers;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Type;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use InvalidArgumentException;

class CollectionObserver
{
    private mixed $deletedAt = null;

    public function creating(Content $content): void
    {
        if (! $content->type_id) {
            $content->type_id = Type::query()->where('type', LayoutTypeEnum::Content)->default()->value('id');
            throw_unless($content->type_id, InvalidArgumentException::class, 'Unable to create content without a type.');
        }

        // Normalize parent_id from loaded relation if needed (nested set).
        if ($content->parent_id !== null) {
            $parent = $content->getRelationValue('parent');
            if ($parent !== null && $content->parent_id !== $parent->id) {
                $content->parent_id = $parent->id;
            }
        }
    }

    public function saving(Content $content): void
    {
        if (method_exists($content, 'nodeCallPendingAction')) {
            $content->nodeCallPendingAction();
        }
    }

    public function deleting(Content $content): void
    {
        if (method_exists($content, 'nodeRefreshNode')) {
            $content->nodeRefreshNode();
        }
    }

    public function deleted(Content $content): void
    {
        if (method_exists($content, 'nodeDeleteDescendants')) {
            $content->nodeDeleteDescendants();
        }

        // Shadow-column maintenance runs in the BelongsToWorkspace trait's
        // `deleting` hook, before this observer fires.

        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }

    public function restoring(Content $content): void
    {
        $this->deletedAt = method_exists($content, 'nodeGetDeletedAtValue')
            ? $content->nodeGetDeletedAtValue()
            : null;
    }

    public function restored(Content $content): void
    {
        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }
}
