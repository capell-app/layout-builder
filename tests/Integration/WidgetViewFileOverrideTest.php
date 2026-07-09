<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\MainContentRenderHookData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualFrontendContextForLoadedLayout;
use Capell\LayoutBuilder\Tests\Fixtures\View\Components\PageBuildingWidget;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    CapellLayoutManager::clearContainerWidgets();
    Blade::component(PageBuildingWidget::class, 'capell::widget.default');
});

afterEach(function (): void {
    CapellLayoutManager::clearContainerWidgets();
});

it('renders a widget view-file override with prepared public widget asset data', function (): void {
    $viewPath = storage_path('framework/testing/page-building-widget-view-file');
    File::ensureDirectoryExists($viewPath);
    File::put($viewPath . '/guide-widget.blade.php', '<section data-page-building-widget>{{ $title }}</section>');
    View::addNamespace('page-building-test', $viewPath);

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => 'guide-widget', 'occurrence' => 1]]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create([
        'key' => 'guide-widget',
        'component' => 'capell.widget.default',
        'view_file' => 'page-building-test::guide-widget',
        'meta' => [
            'field_path' => 'containers.main.widgets.0',
            'signed_editor_url' => 'https://admin.test/widgets/guide-widget?signature=private-signature',
        ],
    ]);
    $asset = Page::factory()->site($site)->withTranslations($language)->create();
    $asset->translation->update(['title' => 'Custom widget asset title']);

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->page($page, 'main', 1)
        ->create();

    app()->instance('capell.frontend.context', new LayoutBuilderResidualFrontendContextForLoadedLayout($layout, $language, $page));

    try {
        $html = resolve(RenderHookRegistry::class)->renderAll(
            RenderHookLocation::MainContent,
            new MainContentRenderHookData(layout: $layout, page: $page),
            scenario: 'frontend-main-layout',
            target: 'capell::layout.main',
        );
    } finally {
        File::deleteDirectory($viewPath);
    }

    expect($html)->toContain('data-page-building-widget')
        ->and($html)->toContain('Custom widget asset title')
        ->and($html)->not->toContain($widget->key)
        ->and($html)->not->toContain('data-model-id="' . $widget->getKey() . '"')
        ->and($html)->not->toContain('field_path')
        ->and($html)->not->toContain('containers.main.widgets.0')
        ->and($html)->not->toContain('https://admin.test/widgets/guide-widget?signature=private-signature')
        ->and($html)->not->toContain('private-signature');
});
