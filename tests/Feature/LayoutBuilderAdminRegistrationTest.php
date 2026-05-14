<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\Admin\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;

it('registers the admin surface through the layout builder package registrar', function (): void {
    $registrar = app(LayoutBuilderAdminRegistrar::class);

    $registrar->register();
    $registrar->register();

    expect($registrar->isRegistered())->toBeTrue()
        ->and(CapellAdmin::getAdminSurfaceRegistry()->resources())->toContain(LayoutResource::class, WidgetResource::class);
});
