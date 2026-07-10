<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\LayoutPresets\Pages;

use Capell\LayoutBuilder\Filament\Resources\LayoutPresets\LayoutPresetResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListLayoutPresets extends ListRecords
{
    #[Override]
    public static function getResource(): string
    {
        return LayoutPresetResource::class;
    }
}
