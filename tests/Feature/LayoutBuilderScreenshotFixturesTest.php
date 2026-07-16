<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;

final class LayoutBuilderScreenshotFixturesTest extends LayoutBuilderTestCase
{
    public function test_it_renders_safe_public_layout_builder_screenshot_fixtures(): void
    {
        $fixtures = [
            '/screenshot-fixtures/layout-builder/main-sidebar' => [
                'Main content with sidebar',
                'A composed page with supporting content',
                'On this page',
                'Strategy content block',
            ],
            '/screenshot-fixtures/layout-builder/full-width' => [
                'Full width content',
                'Full width sections for broad storytelling',
                'Feature grid',
                'Supporting editorial copy',
            ],
            '/screenshot-fixtures/layout-builder/preset-action' => [
                'Layout preset action',
                'Save this container as a preset',
                'Reusable layout patterns',
                'Fixture state',
            ],
            '/screenshot-fixtures/layout-builder/undo-redo-actions' => [
                'Undo and redo actions',
                'Recover from layout changes',
                'Undo mutation',
                'Redo mutation',
            ],
            '/screenshot-fixtures/layout-builder/bulk-change-criteria' => [
                'Bulk change criteria',
                'Scope the layouts to update',
                'Bulk layout operations',
                'Ready for review',
            ],
            '/screenshot-fixtures/layout-builder/bulk-change-review' => [
                'Bulk change review',
                'Review affected layouts before approval',
                'Safe review step',
                'Hash guarded',
            ],
        ];

        foreach ($fixtures as $path => $visibleContent) {
            $response = $this->get($path);

            $response->assertOk();

            foreach ($visibleContent as $content) {
                $response->assertSee($content, false);
            }

            $html = (string) $response->getContent();

            self::assertStringNotContainsString('data-layout-builder-editor', $html);
            self::assertStringNotContainsString('wire:', $html);
            self::assertStringNotContainsString('signed', $html);
            self::assertStringNotContainsString('filament', $html);
        }
    }

    public function test_it_rejects_unknown_layout_builder_screenshot_fixture_screens(): void
    {
        $this->get('/screenshot-fixtures/layout-builder/missing')->assertNotFound();
    }

    public function test_it_renders_bounded_widget_editor_and_public_screenshot_fixtures(): void
    {
        foreach (array_keys(widgetScreenshotFixtureDefinitions()) as $widget) {
            $editorResponse = $this->get(sprintf('/screenshot-fixtures/widgets/%s/editor', $widget));

            self::assertSame(200, $editorResponse->getStatusCode(), $widget . ' editor fixture');

            $editorResponse
                ->assertSee('data-widget-screenshot-fixture="editor"', false);

            $publicResponse = $this->get(sprintf('/screenshot-fixtures/widgets/%s/public', $widget));

            self::assertSame(200, $publicResponse->getStatusCode(), $widget . ' public fixture');

            $publicHtml = (string) $publicResponse
                ->assertSee('data-widget-screenshot-fixture="public"', false)
                ->getContent();

            self::assertStringNotContainsString('wire:', $publicHtml);
            self::assertStringNotContainsString('filament', $publicHtml);
            self::assertStringNotContainsString('signed', $publicHtml);
        }

        $this->get('/screenshot-fixtures/widgets/not-a-widget/public')->assertNotFound();
        $this->get('/screenshot-fixtures/widgets/youtube/unknown')->assertNotFound();
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Repository::class)->set('app.key', 'base64:' . base64_encode('12345678901234567890123456789012'));

        $migrationWorkspace = storage_path('framework/testing-migrations');

        if (! is_dir($migrationWorkspace)) {
            mkdir($migrationWorkspace, 0775, true);
        }
    }

    #[Override]
    protected function defineRoutes($router): void
    {
        assert($router instanceof Router);

        if (! function_exists('registerLayoutBuilderScreenshotFixtureRoutes')) {
            require dirname(__DIR__, 4) . '/workbench/routes/screenshot-fixtures.php';
        }

        if (! function_exists('widgetScreenshotFixtureDefinitions')) {
            require dirname(__DIR__, 4) . '/workbench/routes/screenshot-fixtures-widgets.php';
        }

        registerLayoutBuilderScreenshotFixtureRoutes();
        registerWidgetScreenshotFixtureRoutes();
    }
}
