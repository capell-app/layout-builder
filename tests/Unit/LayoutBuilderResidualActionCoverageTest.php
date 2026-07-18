<?php

declare(strict_types=1);

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\AddWidgetToLayoutContainerAction;
use Capell\LayoutBuilder\Actions\ApplyLayoutSidebarWidgetContributionsAction;
use Capell\LayoutBuilder\Actions\FindReusableWidgetsAction;
use Capell\LayoutBuilder\Actions\GetWidgetContainerWidthAction;
use Capell\LayoutBuilder\Actions\HeroWidgetHasPrimaryHeadingAction;
use Capell\LayoutBuilder\Actions\MakeWidgetAction;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualSidebarContributor;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('builds widget seeder snippets', function (): void {
    $snippet = resolve(MakeWidgetAction::class)->seederSnippet('promo-card', 'Promo Card');

    expect($snippet)->toContain("'key' => 'promo-card'")
        ->and($snippet)->toContain("'name' => 'Promo Card'");
});

it('rejects empty widget scaffold names', function (): void {
    MakeWidgetAction::run('');
})->throws(RuntimeException::class, 'Widget name is required.');

it('adds layout widgets with occurrences and skips existing widgets when requested', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'hero', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'main');

    expect($layout->refresh()->containers['main']['widgets'])->toHaveCount(2)
        ->and($layout->containers['main']['widgets'][1])->toBe([
            'widget_key' => 'hero',
            'occurrence' => 2,
        ]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'main', skipExists: true);

    expect($layout->refresh()->containers['main']['widgets'])->toHaveCount(2);
});

it('throws when adding a widget to a missing layout container', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => []]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'missing');
})->throws(RuntimeException::class, "Container 'missing' not found in layout.");

it('applies sidebar contributions only for existing applicable widgets', function (): void {
    app()->bind(
        LayoutBuilderResidualSidebarContributor::class,
        fn (): LayoutBuilderResidualSidebarContributor => new LayoutBuilderResidualSidebarContributor,
    );
    app()->tag([LayoutBuilderResidualSidebarContributor::class], LayoutSidebarWidgetContributor::TAG);

    Widget::factory()->create(['key' => 'sidebar-search']);
    Widget::factory()->create(['key' => 'other-layout-only']);

    $layout = Layout::factory()->create([
        'key' => 'content',
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'body'],
                ],
            ],
        ],
    ]);

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);
    ApplyLayoutSidebarWidgetContributionsAction::run($layout->refresh());

    expect($layout->refresh()->containers['sidebar']['meta']['colspan'])->toBe(3)
        ->and($layout->containers['sidebar']['widgets'])->toBe([
            [
                'widget_key' => 'sidebar-search',
                'meta' => ['compact' => true],
            ],
        ])
        ->and($layout->widgets)->toBe(['body', 'sidebar-search']);
});

it('resolves widget container widths from meta defaults and frontend resolver services', function (): void {
    $widget = Widget::factory()->create(['meta' => ['container' => ContainerWidthEnum::Small->value]]);

    expect(GetWidgetContainerWidthAction::run($widget))->toBe(ContainerWidthEnum::Small);

    $widget->forceFill(['meta' => []]);
    app()->bind('capell.frontend.layout-container-width-resolver', fn (): Closure => fn (?string $default): ContainerWidthEnum => $default === 'lg'
                ? ContainerWidthEnum::Large
                : ContainerWidthEnum::Full);

    expect(GetWidgetContainerWidthAction::run($widget, ContainerWidthEnum::Large->value))->toBe(ContainerWidthEnum::Large);

    app()->bind('capell.frontend.layout-container-width-resolver', function (): object {
        return new class
        {
            public function resolve(?string $default): ?ContainerWidthEnum
            {
                return $default === null ? ContainerWidthEnum::Medium : null;
            }
        };
    });

    expect(GetWidgetContainerWidthAction::run($widget))->toBe(ContainerWidthEnum::Medium)
        ->and(GetWidgetContainerWidthAction::run($widget, ContainerWidthEnum::ExtraLarge->value))->toBe(ContainerWidthEnum::ExtraLarge)
        ->and(FindReusableWidgetsAction::run('hero'))->toBe([]);
});

it('detects hero headings from page meta and first widget asset translations', function (): void {
    $frontendContext = new FrontendState;
    app()->instance(FrontendContextReader::class, $frontendContext);

    $page = Page::factory()->withTranslations()->create();
    $page->translation->forceFill([
        'meta' => [
            'hero' => '<section><h1>Welcome</h1></section>',
        ],
    ])->save();
    $page->load('translation');
    $emptyWidget = Widget::factory()->create();
    $emptyWidget->setRelation('assets', new EloquentCollection);

    expect(HeroWidgetHasPrimaryHeadingAction::run($emptyWidget, $page))->toBeTrue()
        ->and($frontendContext->getFrontendData('has_primary_heading'))->toBeTrue();

    $assetPage = Page::factory()->withTranslations()->create();
    $assetPage->translation->forceFill(['title' => 'Asset Heading'])->save();
    $assetPage->load('translation');
    $widgetAsset = WidgetAsset::factory()
        ->widget(Widget::factory()->create())
        ->asset($assetPage)
        ->make();
    $widgetAsset->setRelation('asset', $assetPage->load('translation'));

    $assetWidget = Widget::factory()->create();
    $assetWidget->setRelation('assets', new EloquentCollection([$widgetAsset]));

    expect(HeroWidgetHasPrimaryHeadingAction::run($assetWidget, $page))->toBeTrue();
});
