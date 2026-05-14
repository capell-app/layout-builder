<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\LayoutBuilder\Actions\BuildPublicLayoutGraphAction as CoreBuildPublicLayoutGraphAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Widget;
use Capell\Core\Tests\Support\View\Components\PackageAlert;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;

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

it('routes the legacy core public graph action through layout builder package payload contributors', function (): void {
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
        'widgets' => [$widget->key],
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.package-public-widget-payload-contributor', fn (): PublicWidgetPayloadContributor => new class implements PublicWidgetPayloadContributor
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

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return '<section data-package-contributor="' . $widget->key . '"></section>';
        }
    });

    app()->tag('test.package-public-widget-payload-contributor', PublicWidgetPayloadContributor::TAG);

    $graph = CoreBuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $widgetData = $graph->containers[0]->widgets[0];

    expect($widgetData->data['title'])->toBe('Package-backed widget')
        ->and($widgetData->data['package_contributor'])->toBe([
            'widget' => 'package-backed-widget',
            'container' => 'main',
            'occurrence' => 1,
        ])
        ->and($widgetData->html)->toBe('<section data-package-contributor="package-backed-widget"></section>');
});
