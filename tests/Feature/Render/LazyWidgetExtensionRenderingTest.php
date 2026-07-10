<?php

declare(strict_types=1);

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
        resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
            fallbackView: 'lazy-widget-test::widget',
            batchPayloadResolver: RecordingBatchPayloadResolver::class,
        ));
        app()->forgetInstance(FrontendContextReader::class);
        app()->offsetUnset(FrontendContextReader::class);
        config()->set('capell-frontend.public_view_query_guard.enabled', true);
        config()->set('capell-frontend.public_view_query_guard.mode', 'exception');

        $reference = OpaqueWidgetReference::encode([
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
            ->and($response->getContent())->toContain('LAZY &lt;Lazy target&gt;')
            ->not->toContain('lazy-instance', 'state_version', '__capell', 'must-not-leak')
            ->and(RecordingBatchPayloadResolver::$calls)->toBe(1);
    } finally {
        @unlink($viewRoot . '/widget.blade.php');
        @rmdir($viewRoot);
    }
});
