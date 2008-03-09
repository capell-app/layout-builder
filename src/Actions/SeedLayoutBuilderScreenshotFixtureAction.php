<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use RuntimeException;

/**
 * Seeds a set of reusable widgets for disposable screenshot runs so the widget
 * admin index and edit form are captured with realistic records.
 */
final class SeedLayoutBuilderScreenshotFixtureAction
{
    /**
     * @var list<array{key: string, name: string, status: bool}>
     */
    private const array Widgets = [
        ['key' => 'screenshot-homepage-hero', 'name' => 'Homepage hero', 'status' => true],
        ['key' => 'screenshot-customer-testimonials', 'name' => 'Customer testimonials', 'status' => true],
        ['key' => 'screenshot-newsletter-signup', 'name' => 'Newsletter signup', 'status' => true],
        ['key' => 'screenshot-footer-call-to-action', 'name' => 'Footer call to action', 'status' => true],
        ['key' => 'screenshot-seasonal-promotion', 'name' => 'Seasonal promotion banner', 'status' => false],
    ];

    public static function run(): int
    {
        self::assertDisposableScreenshotEnvironment();

        $widgetType = resolve(TypeCreator::class)->defaultWidgetType();

        foreach (self::Widgets as $row) {
            $widget = Widget::query()->firstOrNew(['key' => $row['key']]);
            $widget->fill([
                'name' => $row['name'],
                'blueprint_id' => $widgetType->getKey(),
                'component' => 'capell.layout-builder.widget.default',
                'is_livewire' => false,
                'status' => $row['status'],
                'meta' => ['component' => 'capell.layout-builder.widget.default'],
            ]);

            if (! $widget->exists || $widget->isDirty()) {
                $widget->save();
            }
        }

        return Widget::query()->whereIn('key', array_column(self::Widgets, 'key'))->count();
    }

    private static function assertDisposableScreenshotEnvironment(): void
    {
        $configuredAppPath = getenv('CAPELL_SCREENSHOT_APP_PATH');
        $basePath = realpath(base_path());
        $appPath = is_string($configuredAppPath) ? realpath($configuredAppPath) : false;
        $environment = app()->bound('config') ? config('app.env') : getenv('APP_ENV');

        throw_unless(
            in_array($environment, ['local', 'testing'], true)
                && in_array(getenv('CAPELL_SCREENSHOT_FIXTURE'), ['1', 'true', 'record-state'], true)
                && is_string($basePath)
                && is_string($appPath)
                && $basePath === $appPath,
            RuntimeException::class,
            'Layout Builder screenshot fixtures require the explicit disposable local screenshot environment.',
        );
    }
}
