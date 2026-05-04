<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWidget extends CreateRecord
{
    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Widget);
    }
}
