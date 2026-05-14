<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Widget;
use Capell\Core\Tests\Support\View\Components\PackageAlert;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Data\PublicLayoutWidgetData;

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

it('builds public layout data for selected containers only', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);

    $mainWidget = Widget::factory()->create(['key' => 'main-widget']);
    $sidebarWidget = Widget::factory()->create(['key' => 'sidebar-widget']);

    TranslationFactory::new()
        ->translatable($mainWidget)
        ->language($language)
        ->create([
            'title' => 'Main Widget',
            'content' => '<p>Main content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'key' => 'article',
        'widgets' => [$mainWidget->key, $sidebarWidget->key],
        'containers' => [
            'main' => [
                'label' => 'Main',
                'widgets' => [
                    ['widget_key' => $mainWidget->key, 'occurrence' => 1],
                ],
            ],
            'sidebar' => [
                'label' => 'Sidebar',
                'widgets' => [
                    ['widget_key' => $sidebarWidget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, ['main']);

    expect($graph)->toBeInstanceOf(PublicLayoutGraphData::class)
        ->and($graph->key)->toBe('article')
        ->and($graph->containers)->toHaveCount(1)
        ->and($graph->containers[0])->toBeInstanceOf(PublicLayoutContainerData::class)
        ->and($graph->containers[0]->key)->toBe('main')
        ->and($graph->containers[0]->widgets)->toHaveCount(1)
        ->and($graph->containers[0]->widgets[0])->toBeInstanceOf(PublicLayoutWidgetData::class)
        ->and($graph->containers[0]->widgets[0]->key)->toBe('main-widget')
        ->and($graph->containers[0]->widgets[0]->data['title'])->toBe('Main Widget')
        ->and($graph->containers[0]->widgets[0]->data['content'])->toBe('<p>Main content</p>');
});

it('lets package tagged contributors extend widget payload data and html', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create(['key' => 'featured']);

    TranslationFactory::new()
        ->translatable($widget)
        ->language($language)
        ->create([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$widget->key],
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 3]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-payload-contributor', fn (): PublicWidgetPayloadContributor => new class implements PublicWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'source' => 'package',
                'items' => [
                    ['label' => $containerKey . ':' . $occurrence],
                ],
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return '<section>' . $widget->key . ':' . $containerKey . ':' . $occurrence . '</section>';
        }
    });

    app()->tag(['test.layout-builder-payload-contributor'], PublicWidgetPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $widgetData = $graph->containers[0]->widgets[0];

    expect($widgetData->data)
        ->toMatchArray([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
            'source' => 'package',
            'items' => [
                ['label' => 'main:3'],
            ],
        ])
        ->and($widgetData->html)->toBe('<section>featured:main:3</section>');
});
