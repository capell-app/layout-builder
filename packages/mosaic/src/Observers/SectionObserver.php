<?php

declare(strict_types=1);

namespace Capell\Mosaic\Observers;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Type;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Models\Section;
use InvalidArgumentException;

class SectionObserver
{
    public function creating(Section $section): void
    {
        if (! $section->type_id) {
            $section->type_id = Type::query()->where('type', LayoutTypeEnum::Section)->default()->value('id');
            throw_unless($section->type_id, InvalidArgumentException::class, 'Unable to create content without a type.');
        }

        // Normalize parent_id from loaded relation if needed (nested set).
        if ($section->parent_id !== null) {
            $parent = $section->getRelationValue('parent');
            if ($parent !== null && $section->parent_id !== $parent->id) {
                $section->parent_id = $parent->id;
            }
        }
    }

    public function saving(Section $section): void
    {
        if (method_exists($section, 'nodeCallPendingAction')) {
            $section->nodeCallPendingAction();
        }
    }

    public function deleting(Section $section): void
    {
        if (method_exists($section, 'nodeRefreshNode')) {
            $section->nodeRefreshNode();
        }
    }

    public function deleted(Section $section): void
    {
        if (method_exists($section, 'nodeDeleteDescendants')) {
            $section->nodeDeleteDescendants();
        }

        // Shadow-column maintenance runs in the BelongsToWorkspace trait's
        // `deleting` hook, before this observer fires.

        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }

    public function restoring(Section $section): void {}

    public function restored(Section $section): void
    {
        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }
}
