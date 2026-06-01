<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Actionable;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Data\LayoutAssetBridgeData;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Support\LayoutAssetBridgeRegistry;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

it('registers the admin surface through the layout builder package registrar', function (): void {
    $registrar = resolve(LayoutBuilderAdminRegistrar::class);

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

    resolve(LayoutBuilderAdminRegistrar::class)->register();

    $previewViewPath = view()->getFinder()->find('capell-layout-builder::filament.layout-builder.previews.default');
    $nestedViewDirectory = 'resources/views/layout-builder';

    expect($source)->not->toContain('Capell\\\\Admin\\\\LayoutBuilder\\\\Support\\\\LayoutBuilderAdminRegistrar')
        ->and($previewViewPath)->toContain('packages/layout-builder/resources/views')
        ->and($previewViewPath)->not->toContain($nestedViewDirectory);
});

it('registers the layout builder admin stylesheet through Filament assets', function (): void {
    resolve(LayoutBuilderAdminRegistrar::class)->register();

    $style = collect(FilamentAsset::getStyles(['capell-layout-builder']))
        ->first(fn (Css $asset): bool => $asset->getId() === 'capell-layout-builder-filament');

    expect($style)->toBeInstanceOf(Css::class);

    /** @var Css $style */
    expect($style->getPath())->toEndWith('resources/css/layout-builder/admin/capell-layout-filament.css')
        ->and($style->getRelativePublicPath())->toBe('css/capell-layout-builder/capell-layout-builder-filament.css')
        ->and($style->getHtml()->toHtml())->toBe("<link
            href=\"{$style->getHref()}\"
            rel=\"stylesheet\"
            data-navigate-track
        />");
});

it('registers and resolves layout asset bridges by key', function (): void {
    $registry = new LayoutAssetBridgeRegistry;
    $asset = new LayoutAssetBridgeData(
        key: 'page',
        name: 'Page',
        model: Page::class,
        icon: 'heroicon-o-document',
        color: 'primary',
        label: 'Page',
        component: 'capell-layout-builder::asset.page',
        formClass: stdClass::class,
        createAction: LayoutBuilderAdminRegistrationActionable::class,
        defaultDataAction: LayoutBuilderAdminRegistrationActionable::class,
        hasTranslations: true,
    );

    expect($registry->all())->toBe([])
        ->and($registry->has('page'))->toBeFalse()
        ->and($registry->get('page'))->toBeNull();

    $registry->register($asset);

    expect($registry->has('page'))->toBeTrue()
        ->and($registry->get('page'))->toBe($asset)
        ->and($registry->all())->toBe(['page' => $asset]);
});

final class LayoutBuilderAdminRegistrationActionable implements Actionable
{
    public static function run(mixed ...$parameters): mixed
    {
        return $parameters;
    }
}
