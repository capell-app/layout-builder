<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts;

use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Override;

class LayoutResource extends \Capell\Admin\Filament\Resources\Layouts\LayoutResource
{
    protected static ?string $slug = 'layout-builder/layouts';

    protected static bool $isGloballySearchable = true;

    protected static string $tableConfigurator = LayoutsTable::class;

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_websites');
    }

    #[Override]
    public static function getNavigationParentItem(): ?string
    {
        return null;
    }
}
