<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadContributor;
use Capell\LayoutBuilder\Models\Element;
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
                'capell::element.default' => PackageAlert::class,
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
    $element = Element::factory()->create(['key' => 'package-backed-element']);

    TranslationFactory::new()
        ->translatable($element)
        ->language($language)
        ->create([
            'title' => 'Package-backed element',
            'content' => '<p>Base content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'elements' => [$element->key],
        'containers' => [
            'main' => ['elements' => [['element_key' => $element->key, 'occurrence' => 1]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.package-public-element-payload-contributor', fn (): PublicElementPayloadContributor => new class implements PublicElementPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        public function data(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'package_contributor' => [
                    'element' => $element->key,
                    'container' => $containerKey,
                    'occurrence' => $occurrence,
                ],
            ];
        }

        public function html(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section data-package-contributor="' . $element->key . '"></section>';
        }
    });

    app()->tag('test.package-public-element-payload-contributor', PublicElementPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $elementData = $graph->containers[0]->elements[0];

    expect($elementData->data['title'])->toBe('Package-backed element')
        ->and($elementData->data['package_contributor'])->toBe([
            'element' => 'package-backed-element',
            'container' => 'main',
            'occurrence' => 1,
        ])
        ->and($elementData->html)->toBe('<section data-package-contributor="package-backed-element"></section>');
});
