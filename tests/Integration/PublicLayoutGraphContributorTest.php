<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Tests\Fixtures\View\Components\PackageAlert;

beforeEach(function (): void {
    app()->bind(BladeComponentResolverInterface::class, fn (): BladeComponentResolverInterface => new class implements BladeComponentResolverInterface
    {
        /**
         * @return array<string, class-string>
         */
        public function getClassComponentAliases(): array
        {
            return [
                'capell::widget.default' => PackageAlert::class,
            ];
        }

        /**
         * @return array<string, string>
         */
        public function getClassComponentNamespaces(): array
        {
            return [];
        }
    });
});

it('routes public graph rendering through layout builder package payload contributors', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create(['key' => 'package-backed-widget']);

    TranslationFactory::new()
        ->translatable($widget)
        ->language($language)
        ->create([
            'title' => 'Package-backed widget',
            'content' => '<p>Base content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.package-public-layout-widget-payload-contributor', fn (): PublicLayoutWidgetPayloadContributor => new class implements PublicLayoutWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'package_contributor' => [
                    'widget' => $widget->key,
                    'container' => $containerKey,
                    'occurrence' => $occurrence,
                ],
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section data-package-contributor="' . $widget->key . '"></section>';
        }
    });

    app()->tag('test.package-public-layout-widget-payload-contributor', PublicLayoutWidgetPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $widgetData = $graph->containers[0]->widgets[0];

    expect($widgetData->data['title'])->toBe('Package-backed widget')
        ->and($widgetData->data['package_contributor'])->toBe([
            'widget' => 'package-backed-widget',
            'container' => 'main',
            'occurrence' => 1,
        ])
        ->and($widgetData->html)->toBe('<section data-package-contributor="package-backed-widget"></section>');
});

it('renders a page-scoped widget asset through its named public layout container', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create(['key' => 'page-building-guide']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $asset = Page::factory()->site($site)->withTranslations($language)->create();
    $asset->translation->update(['title' => 'Build pages your way']);

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->page($page, 'main', 1)
        ->create();

    app()->singleton('test.page-building-public-layout-widget-payload-contributor', fn (): PublicLayoutWidgetPayloadContributor => new class implements PublicLayoutWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            $assetTitle = $widget->assets->first()?->asset?->translation?->title;

            return '<section data-page-building-asset>' . e($assetTitle) . '</section>';
        }
    });
    app()->tag('test.page-building-public-layout-widget-payload-contributor', PublicLayoutWidgetPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);

    expect($graph->containers[0]->key)->toBe('main')
        ->and($graph->containers[0]->widgets[0]->key)->toBe('page-building-guide')
        ->and($graph->containers[0]->widgets[0]->html)->toBe('<section data-page-building-asset>Build pages your way</section>');
});
