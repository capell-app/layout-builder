<?php

declare(strict_types=1);

namespace Capell\Tags\Models\Concerns;

use ArrayAccess;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Tags\Models\Tag;

/**
 * @mixin Page
 */
trait HasTags
{
    use \Spatie\Tags\HasTags;

    public function syncTagsWithType(array|ArrayAccess $tags, ?string $type = null): static
    {
        /** @var Tag $className */
        $className = static::getTagClassName();

        if ($this->languages->isNotEmpty()) {
            $tagRecords = collect();

            $this->languages->each(function (Language $language) use (&$tagRecords, &$tags, $className, $type): void {
                $tagRecords->push($className::findOrCreate($tags, $type, $language->code));
            });

            $tags = $tagRecords->flatten();
        } else {
            $tags = collect($className::findOrCreate($tags, $type));
        }

        $this->syncTagIds($tags->pluck('id')->toArray(), $type);

        return $this;
    }
}
