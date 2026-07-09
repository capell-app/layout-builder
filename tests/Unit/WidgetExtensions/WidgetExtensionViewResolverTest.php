<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Exceptions\MissingWidgetExtensionViewException;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionViewResolver;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    $this->widgetViewRoot = sys_get_temp_dir() . '/capell-widget-views-' . bin2hex(random_bytes(6));
    $this->themeViewRoot = $this->widgetViewRoot . '/theme';
    $this->fallbackViewRoot = $this->widgetViewRoot . '/fallback';

    mkdir($this->themeViewRoot . '/widgets/capell-app', 0777, true);
    mkdir($this->fallbackViewRoot, 0777, true);

    View::prependNamespace('capell', $this->themeViewRoot);
    View::addNamespace('capell-widget-slideshow', $this->fallbackViewRoot);
});

afterEach(function (): void {
    if (! is_dir($this->widgetViewRoot)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->widgetViewRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($this->widgetViewRoot);
});

it('prefers the stable active-theme widget slot', function (): void {
    file_put_contents($this->themeViewRoot . '/widgets/capell-app/slideshow.blade.php', 'theme');
    file_put_contents($this->fallbackViewRoot . '/widget.blade.php', 'fallback');

    $view = app(WidgetExtensionViewResolver::class)->resolve(ExampleWidgetExtensionDefinition::make());

    expect($view)->toBe('capell::widgets.capell-app.slideshow');
});

it('uses the package fallback when the theme does not override the widget', function (): void {
    file_put_contents($this->fallbackViewRoot . '/widget.blade.php', 'fallback');

    $view = app(WidgetExtensionViewResolver::class)->resolve(ExampleWidgetExtensionDefinition::make());

    expect($view)->toBe('capell-widget-slideshow::widget');
});

it('does not cache a fallback across request-time theme changes', function (): void {
    file_put_contents($this->fallbackViewRoot . '/widget.blade.php', 'fallback');
    $resolver = app(WidgetExtensionViewResolver::class);
    $definition = ExampleWidgetExtensionDefinition::make();

    expect($resolver->resolve($definition))->toBe('capell-widget-slideshow::widget');

    file_put_contents($this->themeViewRoot . '/widgets/capell-app/slideshow.blade.php', 'theme');

    expect($resolver->resolve($definition))->toBe('capell::widgets.capell-app.slideshow');
});

it('fails loudly with package diagnostics when no render view exists', function (): void {
    expect(fn (): string => app(WidgetExtensionViewResolver::class)->resolve(ExampleWidgetExtensionDefinition::make()))
        ->toThrow(
            MissingWidgetExtensionViewException::class,
            'capell-app/widget-slideshow',
        );
});

it('does not let a theme override mask a missing package fallback', function (): void {
    file_put_contents($this->themeViewRoot . '/widgets/capell-app/slideshow.blade.php', 'theme');

    expect(fn (): string => app(WidgetExtensionViewResolver::class)->resolve(ExampleWidgetExtensionDefinition::make()))
        ->toThrow(MissingWidgetExtensionViewException::class, 'capell-widget-slideshow::widget');
});
