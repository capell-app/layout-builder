<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;

it('registers the admin surface through the layout builder package registrar', function (): void {
    $registrar = app(LayoutBuilderAdminRegistrar::class);

    $registrar->register();
    $registrar->register();

    expect($registrar->isRegistered())->toBeTrue()
        ->and(CapellAdmin::getAdminSurfaceRegistry()->resources())->toContain(
            LayoutResource::class,
            WidgetResource::class,
        );
});

it('owns admin registration without delegating to the legacy admin registrar', function (): void {
    $reflection = new ReflectionClass(LayoutBuilderAdminRegistrar::class);
    $source = file_get_contents((string) $reflection->getFileName());

    app(LayoutBuilderAdminRegistrar::class)->register();

    expect($source)->not->toContain('Capell\\\\Admin\\\\LayoutBuilder\\\\Support\\\\LayoutBuilderAdminRegistrar')
        ->and(view()->exists('capell-layout-builder::filament.layout-builder.previews.default'))->toBeTrue()
        ->and(view()->getFinder()->find('capell-layout-builder::filament.layout-builder.previews.default'))
        ->toContain('packages/layout-builder/resources/views');
});
