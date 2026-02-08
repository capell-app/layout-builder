<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Components\Forms\Page;

use Capell\Blog\Filament\Components\Forms\TagsInput;
use Capell\Core\Enums\TypeEnum;

class PageTagsInput extends TagsInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->type(TypeEnum::Page->value);
    }
}
