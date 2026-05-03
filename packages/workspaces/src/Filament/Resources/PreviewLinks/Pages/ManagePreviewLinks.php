<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\PreviewLinks\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Workspaces\Enums\ResourceEnum;
use Capell\Workspaces\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePreviewLinks extends ManageRecords
{
    /** @return class-string<PreviewLinkResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::PreviewLink);
    }

    protected function getActions(): array
    {
        return [];
    }
}
