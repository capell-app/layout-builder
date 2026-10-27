<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\SeedLayoutBuilderScreenshotFixtureAction;
use Capell\LayoutBuilder\Models\Widget;

function withLayoutBuilderScreenshotFixtureEnvironment(Closure $callback): void
{
    putenv('CAPELL_SCREENSHOT_FIXTURE=record-state');
    putenv('CAPELL_SCREENSHOT_APP_PATH=' . base_path());

    try {
        $callback();
    } finally {
        putenv('CAPELL_SCREENSHOT_FIXTURE');
        putenv('CAPELL_SCREENSHOT_APP_PATH');
    }
}

it('seeds reusable screenshot widgets idempotently', function (): void {
    withLayoutBuilderScreenshotFixtureEnvironment(function (): void {
        expect(SeedLayoutBuilderScreenshotFixtureAction::run())->toBe(5)
            ->and(SeedLayoutBuilderScreenshotFixtureAction::run())->toBe(5);
    });
});

it('refuses to seed outside the disposable screenshot environment', function (): void {
    expect(fn (): int => SeedLayoutBuilderScreenshotFixtureAction::run())
        ->toThrow(RuntimeException::class, 'explicit disposable local screenshot environment');
});

it('requires --force on the layout builder screenshot fixture command', function (): void {
    $this->artisan('capell:layout-builder-screenshot-fixture')->assertFailed();
});

it('preserves a foreign row occupying a screenshot widget identity', function (): void {
    $widget = Widget::factory()->create(['key' => 'screenshot-homepage-hero', 'name' => 'Another owner']);
    $before = $widget->refresh()->getRawOriginal();

    withLayoutBuilderScreenshotFixtureEnvironment(function (): void {
        expect(fn (): int => SeedLayoutBuilderScreenshotFixtureAction::run())
            ->toThrow(RuntimeException::class, 'no creation receipt');
    });

    expect($widget->refresh()->getRawOriginal())->toBe($before);
});
