<?php

declare(strict_types=1);

namespace Capell\Layout\Observers;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Type;
use Capell\Core\Observers\Concerns\DraftsAndNestedSetEvents;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Content;
use InvalidArgumentException;

class ContentObserver
{
    use DraftsAndNestedSetEvents;

    private mixed $deletedAt = null;

    public function creating(Content $content): void
    {
        // Existing type defaulting
        if (! $content->type_id) {
            $content->type_id = Type::query()->where('type', LayoutTypeEnum::Content)->default()->value('id');
            throw_unless($content->type_id, InvalidArgumentException::class, 'Unable to create content without a type.');
        }

        $this->initializeNewModel($content);
    }

    public function saving(Content $content): void
    {
        $this->beforeSaving($content);

        if ($content->visible_from?->isNowOrFuture()) {
            $content->is_published = true;
        }
    }

    public function deleting(Content $content): void
    {
        $this->beforeDeleting($content);
    }

    public function deleted(Content $content): void
    {
        $this->afterDeleted($content);
        // Flush caches impacted by content changes
        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }

    public function restoring(Content $content): void
    {
        $this->deletedAt = $this->beforeRestoring($content);
    }

    public function restored(Content $content): void
    {
        $this->afterRestored($content, $this->deletedAt);
        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }
}
