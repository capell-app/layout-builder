<?php

declare(strict_types=1);

namespace Capell\Layout\Observers;

use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Content;
use InvalidArgumentException;

class ContentObserver
{
    public function creating(Content $content): void
    {
        if (! $content->type_id) {
            $content->type_id = Type::query()->where('type', LayoutTypeEnum::Content)->default()->value('id');
            throw_unless($content->type_id, InvalidArgumentException::class, 'Unable to create content without a type.');
        }
    }
}
