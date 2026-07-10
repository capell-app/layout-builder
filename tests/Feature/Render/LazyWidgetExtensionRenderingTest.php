<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Support\Widgets\OpaqueWidgetReference;
use Capell\LayoutBuilder\Http\Controllers\LazyLayoutWidgetController;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingBatchPayloadResolver;
use Illuminate\Support\Facades\View;

it('builds and explicitly supplies a typed payload before rendering a lazy extension target', function (): void {
    $viewRoot = sys_get_temp_dir() . '/capell-lazy-widget-' . bin2hex(random_bytes(6));
    mkdir($viewRoot, 0777, true);
    file_put_contents($viewRoot . '/widget.blade.php', '<article>LAZY {{ $widget->title }}</article>');
    View::addNamespace('lazy-widget-test', $viewRoot);

    try {
        RecordingBatchPayloadResolver::$calls = 0;
        RecordingBatchPayloadResolver::$mode = 'valid';
        RecordingBatchPayloadResolver::$lastLanguageCode = null;
        resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
            fallbackView: 'lazy-widget-test::widget',
            batchPayloadResolver: RecordingBatchPayloadResolver::class,
        ));
        app()->forgetInstance(FrontendContextReader::class);
        app()->offsetUnset(FrontendContextReader::class);
        config()->set('capell-frontend.public_view_query_guard.enabled', true);
        config()->set('capell-frontend.public_view_query_guard.mode', 'exception');

        $language = Language::factory()->createOne(['code' => 'cy']);
        $site = Site::factory()->createOne(['language_id' => $language->id]);
        $layout = Layout::factory()->site($site)->createOne();
        $page = Page::factory()
            ->site($site)
            ->layout($layout)
            ->withTranslations($language, ['title' => 'Cynnwys'], slug: '/cynnwys')
            ->createOne();
        $theme = $site->theme;

        $reference = OpaqueWidgetReference::encode([
            'context' => [
                'version' => 1,
                'purpose' => 'widget-interaction',
                'site_id' => $site->getKey(),
                'page_type' => $page->getMorphClass(),
                'page_id' => $page->getKey(),
                'language_id' => $language->getKey(),
                'layout_id' => $layout->getKey(),
                'theme_id' => $theme?->getKey(),
            ],
            'type' => 'capell-app.slideshow',
            'data' => [
                'title' => '<Lazy target>',
                '__capell' => [
                    'instance_id' => 'lazy-instance',
                    'state_version' => 2,
                    'editor_url' => 'must-not-leak',
                ],
            ],
        ]);

        $response = (new LazyLayoutWidgetController)($reference);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('Cache-Control'))->toContain('private', 'no-store')
            ->and($response->getContent())->toContain('LAZY &lt;Lazy target&gt;')
            ->not->toContain('lazy-instance', 'state_version', '__capell', 'must-not-leak')
            ->and(RecordingBatchPayloadResolver::$calls)->toBe(1)
            ->and(RecordingBatchPayloadResolver::$lastLanguageCode)->toBe('cy');
    } finally {
        @unlink($viewRoot . '/widget.blade.php');
        @rmdir($viewRoot);
    }
});

it('rejects missing or mismatched lazy widget context with a generic no-store 404', function (string $invalidContext): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        batchPayloadResolver: RecordingBatchPayloadResolver::class,
    ));
    $language = Language::factory()->createOne(['code' => 'en']);
    $site = Site::factory()->createOne(['language_id' => $language->id]);
    $layout = Layout::factory()->site($site)->createOne();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->createOne();
    $context = [
        'version' => 1,
        'purpose' => 'widget-interaction',
        'site_id' => $site->getKey(),
        'page_type' => $page->getMorphClass(),
        'page_id' => $page->getKey(),
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'theme_id' => $site->theme?->getKey(),
    ];

    if ($invalidContext === 'missing') {
        $context = null;
    } elseif ($invalidContext === 'cross-site') {
        $context['site_id'] = Site::factory()->createOne()->getKey();
    } elseif ($invalidContext === 'wrong-page') {
        $context['page_id'] = Page::factory()->createOne()->getKey();
    } elseif ($invalidContext === 'wrong-language') {
        $context['language_id'] = Language::factory()->createOne(['code' => 'fr'])->getKey();
    } elseif ($invalidContext === 'wrong-theme') {
        $context['theme_id'] = Theme::factory()->createOne()->getKey();
    }

    $referenceData = [
        'type' => 'capell-app.slideshow',
        'data' => [
            'title' => 'Never render',
            '__capell' => ['instance_id' => 'invalid-context', 'state_version' => 2],
        ],
    ];
    if (is_array($context)) {
        $referenceData['context'] = $context;
    }

    $response = (new LazyLayoutWidgetController)(OpaqueWidgetReference::encode($referenceData));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Cache-Control'))->toContain('private', 'no-store')
        ->and($response->getContent())->toBe('');
})->with(['missing', 'cross-site', 'wrong-page', 'wrong-language', 'wrong-theme']);
