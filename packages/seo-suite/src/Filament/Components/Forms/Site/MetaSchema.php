<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Components\Forms\Site;

use Capell\SeoSuite\Enums\MetaSchemaEnum;
use Filament\FormBuilder\Components\TagsInput;
use Override;

class MetaSchema extends TagsInput
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.meta_schema'))
            ->suggestions(fn (): array => MetaSchemaEnum::getComponents());
    }

    public static function getDefaultName(): ?string
    {
        return 'meta_schema';
    }
}
