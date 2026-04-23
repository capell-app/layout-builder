<?php

declare(strict_types=1);

namespace Capell\Media\Filament\Resources\Media\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Media\Enums\ResourceEnum;
use Capell\Media\Filament\Resources\Media\MediaResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListMedia extends ListRecords
{
    /** @return class-string<MediaResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Media);
    }
}
