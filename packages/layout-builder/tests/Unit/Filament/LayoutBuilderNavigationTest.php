<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;

it('keeps layouts and widgets as primary content navigation items', function (): void {
    expect(LayoutResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_content'))
        ->and(LayoutResource::getNavigationParentItem())->toBeNull()
        ->and(LayoutResource::getNavigationItems()[0]->getSort())->toBe(4)
        ->and(WidgetResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_content'))
        ->and(WidgetResource::getNavigationParentItem())->toBeNull()
        ->and(WidgetResource::getNavigationItems()[0]->getSort())->toBe(5);
});
