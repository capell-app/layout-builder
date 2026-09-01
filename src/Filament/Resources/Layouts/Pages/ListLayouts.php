<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Pages;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Filament\Resources\LayoutBuilderResource;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Override;

class ListLayouts extends \Capell\Admin\Filament\Resources\Layouts\Pages\ListLayouts
{
    /** @return class-string<LayoutBuilderResource> */
    #[Override]
    public static function getResource(): string
    {
        /** @var class-string<LayoutBuilderResource> $resource */
        $resource = AdminSurfaceLookup::resource(
            ResourceEnum::Layout,
            LayoutBuilderAdminRegistrar::LAYOUT_RESOURCE_NAME,
        );

        return $resource;
    }
}
