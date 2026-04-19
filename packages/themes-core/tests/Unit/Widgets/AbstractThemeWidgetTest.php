<?php

declare(strict_types=1);

use Capell\Themes\Core\Widgets\AbstractThemeWidget;

$makeWidget = (fn (string $view = 'stub::widget'): AbstractThemeWidget => new class($view) extends AbstractThemeWidget
{
    public string $name = 'Test Widget';

    public string $description = 'A test widget.';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Hello'],
        ['name' => 'count', 'label' => 'Count', 'type' => 'number'],
    ];

    public function __construct(public string $view) {}
});

test('defaults() returns field default values keyed by name', function () use ($makeWidget): void {
    $widget = $makeWidget();
    expect($widget->defaults())->toBe(['title' => 'Hello', 'count' => null]);
});

test('fieldNames() returns all field names', function () use ($makeWidget): void {
    $widget = $makeWidget();
    expect($widget->fieldNames())->toBe(['title', 'count']);
});

test('make() returns a new instance of the concrete class', function (): void {
    $widget = new class extends AbstractThemeWidget
    {
        public string $name = 'Test Widget';

        public string $description = 'A test widget.';

        public string $view = 'stub::widget';
    };
    expect($widget::make())->toBeInstanceOf($widget::class);
});

test('fallbackRender uses data title when supplied', function () use ($makeWidget): void {
    $widget = $makeWidget();
    $html = $widget->render(['title' => 'My Title']);
    expect($html)->toContain('My Title');
});

test('fallbackRender escapes dangerous characters', function () use ($makeWidget): void {
    $widget = $makeWidget();
    $html = $widget->render(['title' => '<script>xss</script>']);
    expect($html)->not->toContain('<script>');
    expect($html)->toContain('&lt;script&gt;');
});
