<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Resources\Widgets\WidgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWidget extends CreateRecord
{
    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Widget);
    }
}
