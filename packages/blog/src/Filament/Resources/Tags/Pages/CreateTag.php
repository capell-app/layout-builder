<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Tags\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Override;

class CreateTag extends CreateRecord
{
    use Translatable;

    /** @return class-string<TagResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Tag);
    }

    protected function getActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
