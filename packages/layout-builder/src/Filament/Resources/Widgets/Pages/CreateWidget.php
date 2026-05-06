<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWidget extends CreateRecord
{
    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Widget);
    }
}
