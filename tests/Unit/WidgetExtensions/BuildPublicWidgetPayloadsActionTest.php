<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\WidgetExtensions\BuildPublicWidgetPayloadsAction;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionInputFactory;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleRenderData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingBatchPayloadResolver;

beforeEach(function (): void {
    RecordingBatchPayloadResolver::$calls = 0;
    RecordingBatchPayloadResolver::$mode = 'valid';
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        batchPayloadResolver: RecordingBatchPayloadResolver::class,
    ));
});

it('upcasts validates and batches top-level and nested widget payloads exactly once', function (): void {
    $context = widgetExtensionContext([
        widgetExtensionBlock('first', ['title' => 'First'], stateVersion: 1),
        ['interaction' => ['target_widget' => widgetExtensionBlock('second', ['title' => 'Second'])]],
        widgetExtensionBlock('first', ['title' => 'Duplicate']),
        ['type' => 'unknown.widget', 'data' => ['title' => 'Opaque']],
    ]);

    $payloads = resolve(BuildPublicWidgetPayloadsAction::class)->build($context);

    expect(RecordingBatchPayloadResolver::$calls)->toBe(1)
        ->and($payloads)->toHaveCount(2)
        ->and($payloads['first'])->toBeInstanceOf(ExampleRenderData::class)
        ->and($payloads['first']->title)->toBe('First')
        ->and($payloads['second']->title)->toBe('Second');
});

it('quarantines invalid input and failed or incomplete resolver results', function (string $mode): void {
    RecordingBatchPayloadResolver::$mode = $mode;
    $context = widgetExtensionContext([
        widgetExtensionBlock('valid', ['title' => 'Visible']),
        widgetExtensionBlock('extra', ['title' => 'Hidden', 'editor_secret' => 'reject']),
    ]);

    expect(resolve(BuildPublicWidgetPayloadsAction::class)->build($context))->toBe([])
        ->and(RecordingBatchPayloadResolver::$calls)->toBe(1);
})->with(['throw', 'wrong', 'missing']);

it('converts validated input to the declared render data without a resolver', function (): void {
    $registry = new WidgetExtensionRegistry;
    $registry->register(ExampleWidgetExtensionDefinition::make(
        batchPayloadResolver: null,
    ));
    app()->instance(WidgetExtensionRegistry::class, $registry);
    app()->forgetInstance(WidgetExtensionStateWalker::class);
    app()->forgetInstance(BuildPublicWidgetPayloadsAction::class);

    $payloads = resolve(BuildPublicWidgetPayloadsAction::class)->build(widgetExtensionContext([
        widgetExtensionBlock('direct', ['title' => 'Direct']),
    ]));

    expect($payloads['direct'])->toBeInstanceOf(ExampleRenderData::class)
        ->and($payloads['direct']->title)->toBe('Direct');
});

it('quarantines invalid input types and configured field bounds before resolver boundaries', function (mixed $title): void {
    $payloads = resolve(BuildPublicWidgetPayloadsAction::class)->build(widgetExtensionContext([
        widgetExtensionBlock('invalid', ['title' => $title]),
    ]));

    expect($payloads)->toBe([])
        ->and(RecordingBatchPayloadResolver::$calls)->toBe(0);
})->with([
    'wrong type' => 123,
    'over bound' => str_repeat('x', 41),
]);

it('treats structurally recognized unknown widgets as terminal opaque state', function (): void {
    $payloads = resolve(BuildPublicWidgetPayloadsAction::class)->build(widgetExtensionContext([[
        'type' => 'unavailable.vendor-widget',
        'data' => [
            'preserve' => true,
            'nested_target' => widgetExtensionBlock('hidden-canonical', ['title' => 'Must not resolve']),
        ],
    ]]));

    expect($payloads)->toBe([])
        ->and(RecordingBatchPayloadResolver::$calls)->toBe(0);
});

it('traverses typed generic wrappers to discover registered nested widget targets', function (): void {
    $context = new FrontendRenderContextData(null, null, null, null, null);
    $payloads = resolve(BuildPublicWidgetPayloadsAction::class)->buildForSources([[
        'layout_wrapper' => [
            'type' => 'container',
            'data' => [
                'target_widget' => widgetExtensionBlock('wrapped-target', ['title' => 'Wrapped']),
            ],
        ],
    ]], $context);

    expect($payloads)->toHaveKey('wrapped-target')
        ->and($payloads['wrapped-target']->title)->toBe('Wrapped')
        ->and(RecordingBatchPayloadResolver::$calls)->toBe(1);
});

it('fingerprints the centralized API widget version and typed schema contract deterministically', function (): void {
    $firstRegistry = new WidgetExtensionRegistry;
    $firstRegistry->register(ExampleWidgetExtensionDefinition::make(stateVersion: 1));
    $first = new BuildPublicWidgetPayloadsAction(
        new WidgetExtensionStateWalker($firstRegistry),
        resolve(WidgetExtensionInputFactory::class),
        app(),
        $firstRegistry,
    );

    $versionRegistry = new WidgetExtensionRegistry;
    $versionRegistry->register(ExampleWidgetExtensionDefinition::make(stateVersion: 2));
    $versioned = new BuildPublicWidgetPayloadsAction(
        new WidgetExtensionStateWalker($versionRegistry),
        resolve(WidgetExtensionInputFactory::class),
        app(),
        $versionRegistry,
    );

    $schemaRegistry = new WidgetExtensionRegistry;
    $schemaRegistry->register(ExampleWidgetExtensionDefinition::make(
        stateVersion: 1,
        inputData: ExampleRenderData::class,
    ));
    $schemaChanged = new BuildPublicWidgetPayloadsAction(
        new WidgetExtensionStateWalker($schemaRegistry),
        resolve(WidgetExtensionInputFactory::class),
        app(),
        $schemaRegistry,
    );

    expect($first->fingerprint())->toBe($first->fingerprint())
        ->not->toBe($versioned->fingerprint(), $schemaChanged->fingerprint())
        ->and($first->fingerprint())->toMatch('/^[a-f0-9]{64}$/');
});

/** @param array<int, mixed> $content */
function widgetExtensionContext(array $content): FrontendRenderContextData
{
    $language = Language::factory()->createOne(['code' => 'en']);
    $site = Site::factory()->createOne(['language_id' => $language->id]);
    $page = Page::factory()
        ->site($site)
        ->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations($language, ['title' => 'Widget page', 'content' => $content], slug: '/widget', contentStructure: ContentStructure::Blocks)
        ->create();
    $page->setRelation('translation', $page->translations()->firstOrFail());

    return new FrontendRenderContextData($page, $site, $language, null, null);
}

/** @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function widgetExtensionBlock(string $identity, array $data, int $stateVersion = 2): array
{
    return [
        'type' => 'capell-app.slideshow',
        'data' => [
            ...$data,
            '__capell' => [
                'instance_id' => $identity,
                'state_version' => $stateVersion,
                'editor_url' => 'secret',
            ],
        ],
    ];
}
