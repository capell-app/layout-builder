<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\TagsInput;
use Capell\Core\Enums\TagTypeEnum;

class ContentTagsInput extends TagsInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->type(TagTypeEnum::CONTENT->value);
    }
}
