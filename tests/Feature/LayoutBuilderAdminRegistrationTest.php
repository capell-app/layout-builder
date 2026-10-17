<?php

declare(strict_types=1);

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Layouts\LayoutResource as CoreLayoutResource;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Data\AdminWidgetPreviewData;
use Capell\LayoutBuilder\Data\LayoutAssetBridgeData;
use Capell\LayoutBuilder\Filament\Resources\LayoutBuilderResource;
use Capell\LayoutBuilder\Filament\Resources\LayoutPresets\LayoutPresetResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Support\Assets\FileVersionedCss;
use Capell\LayoutBuilder\Support\LayoutAssetBridgeRegistry;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderAdminRegistrationActionable;
use Capell\Tests\Support\RenderedHtml;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

it('registers the admin surface through the layout builder package registrar', function (): void {
    $registrar = resolve(LayoutBuilderAdminRegistrar::class);

    $registrar->register();
    $registrar->register();

    expect($registrar->isRegistered())->toBeTrue()
        ->and(CapellAdmin::getAdminSurfaceRegistry()->resources())->toContain(
            LayoutBuilderResource::class,
            WidgetResource::class,
        );
});

it('uses a distinct layout resource name alongside the core layout resource', function (): void {
    resolve(LayoutBuilderAdminRegistrar::class)->register();

    $resources = CapellAdmin::getAdminSurfaceRegistry()->resourcesForGroup(ResourceEnum::Layout->name);

    expect(array_keys($resources))->toEqualCanonicalizing([
        'default',
        LayoutBuilderAdminRegistrar::LAYOUT_RESOURCE_NAME,
        'preset',
    ]);

    expect($resources['default'])->toBe(CoreLayoutResource::class)
        ->and($resources[LayoutBuilderAdminRegistrar::LAYOUT_RESOURCE_NAME])->toBe(LayoutBuilderResource::class)
        ->and($resources['preset'])->toBe(LayoutPresetResource::class);
});

it('groups layout builder resources under web pages navigation', function (): void {
    expect(LayoutResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_websites'))
        ->and(LayoutResource::getNavigationParentItem())->toBeNull()
        ->and(LayoutResource::getNavigationSort())->toBe(3)
        ->and(WidgetResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_websites'))
        ->and(WidgetResource::getNavigationParentItem())->toBeNull()
        ->and(WidgetResource::getNavigationSort())->toBe(2);
});

it('renders the registered admin preview with hydrated content', function (): void {
    resolve(LayoutBuilderAdminRegistrar::class)->register();

    $preview = new AdminWidgetPreviewData(
        view: 'capell-layout-builder::filament.layout-builder.previews.default',
        label: 'Fallback label',
        title: 'Editorial feature',
        excerpt: 'An editor-owned summary.',
        image: null,
        typeLabel: null,
        icon: null,
        assetCount: 0,
        hasPageAssets: false,
        usesPageContent: false,
    );
    $html = view('capell-layout-builder::filament.layout-builder.previews.default', ['previewData' => $preview])->render();
    $document = new RenderedHtml($html);

    expect($document->count('//div[normalize-space(text())="Editorial feature"]'))->toBe(1)
        ->and($document->count('//p[normalize-space(.)="An editor-owned summary."]'))->toBe(1)
        ->and($document->count('//img'))->toBe(0);
});

it('registers the layout builder admin stylesheet through Filament assets', function (): void {
    resolve(LayoutBuilderAdminRegistrar::class)->register();

    $style = collect(FilamentAsset::getStyles(['capell-layout-builder']))
        ->first(fn (Css $asset): bool => $asset->getId() === 'capell-layout-builder-filament');

    expect($style)->toBeInstanceOf(FileVersionedCss::class);

    /** @var Css $style */
    $stylePath = $style->getPath();
    throw_unless(is_string($stylePath), RuntimeException::class, 'Expected layout builder stylesheet path.');
    $expectedVersion = (string) filemtime($stylePath);

    expect($stylePath)->toEndWith('resources/css/layout-builder/admin/capell-layout-filament.css')
        ->and($style->getRelativePublicPath())->toBe('css/capell-layout-builder/capell-layout-builder-filament.css')
        ->and($style->getVersion())->toBe($expectedVersion)
        ->and($style->getHref())->toBe(asset($style->getRelativePublicPath()) . '?v=' . $style->getVersion())
        ->and($style->getHtml()->toHtml())->toBe("<link
            href=\"{$style->getHref()}\"
            rel=\"stylesheet\"
            data-navigate-track
        />");
});

it('keeps contained admin tabs visibly focused in both themes', function (): void {
    // Required hooks and values protect the visual contract independently of formatting.
    $stylesheet = file_get_contents(dirname(__DIR__, 2) . '/resources/css/layout-builder/admin/capell-layout-filament.css');

    expect($stylesheet)
        ->toMatch('/\.fi\-tabs\.fi\-contained\s+\.fi\-tabs\-item\s*:\s*focus\-visible/')
        ->toMatch('/outline\s*:\s*2px\s+solid\s+var\s*\(\s*\-\-primary\-600\s*\)\s*/')
        ->toMatch('/\.dark\s+\.fi\-tabs\.fi\-contained\s+\.fi\-tabs\-item\s*:\s*focus\-visible/')
        ->toMatch('/outline\-color\s*:\s*var\s*\(\s*\-\-primary\-400\s*\)\s*/');
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

it('refuses to register the admin surface when the published asset directory is missing', function (): void {
    $publishes = dirname(__DIR__, 2) . '/publishes';
    $held = $publishes . '.held';

    throw_unless(rename($publishes, $held), RuntimeException::class, 'Could not hold the publish directory for the test.');

    app()->forgetInstance(LayoutBuilderAdminRegistrar::REGISTRATION_FLAG);

    try {
        expect(fn () => new LayoutBuilderAdminRegistrar(app())->register())
            ->toThrow(RuntimeException::class, 'Publish directory not found.');
    } finally {
        rename($held, $publishes);
    }
});
