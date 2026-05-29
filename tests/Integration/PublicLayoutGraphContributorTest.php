<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;
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
                'capell::block.default' => PackageAlert::class,
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
    $block = Widget::factory()->create(['key' => 'package-backed-block']);

    TranslationFactory::new()
        ->translatable($block)
        ->language($language)
        ->create([
            'title' => 'Package-backed block',
            'content' => '<p>Base content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $block->key, 'occurrence' => 1]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.package-public-block-payload-contributor', fn (): PublicBlockPayloadContributor => new class implements PublicBlockPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'package_contributor' => [
                    'block' => $block->key,
                    'container' => $containerKey,
                    'occurrence' => $occurrence,
                ],
            ];
        }

        public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section data-package-contributor="' . $block->key . '"></section>';
        }
    });

    app()->tag('test.package-public-block-payload-contributor', PublicBlockPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $blockData = $graph->containers[0]->blocks[0];

    expect($blockData->data['title'])->toBe('Package-backed block')
        ->and($blockData->data['package_contributor'])->toBe([
            'block' => 'package-backed-block',
            'container' => 'main',
            'occurrence' => 1,
        ])
        ->and($blockData->html)->toBe('<section data-package-contributor="package-backed-block"></section>');
});
