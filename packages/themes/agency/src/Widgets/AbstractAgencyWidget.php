<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

/**
 * Base class shared by all Agency theme widgets.
 *
 * Mosaic is optional — widgets do not extend any Mosaic class. If Mosaic
 * is present the ServiceProvider registers these widgets via duck-typing.
 */
abstract class AbstractAgencyWidget
{
    public string $name;

    public string $description;

    public string $view;

    public string $icon = 'heroicon-o-sparkles';

    /**
     * @var array<int, array{name: string, label: string, type: string, default?: mixed, options?: array<string, string>, required?: bool}>
     */
    public array $fields = [];

    public static function make(): static
    {
        return new static;
    }

    /**
     * Render the widget to HTML using the configured Blade view.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(array $data = []): string
    {
        $data = array_merge($this->defaults(), $data);

        if (function_exists('view') && function_exists('app')) {
            try {
                return (string) view($this->view, $data)->render();
            } catch (\Throwable $e) {
                // Fall through to fallbackRender when no Laravel app / view factory is available.
            }
        }

        return $this->fallbackRender($data);
    }

    /**
     * Default values used when a field is not supplied.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];
        foreach ($this->fields as $field) {
            $defaults[$field['name']] = $field['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * Minimal fallback HTML when Laravel view factory is unavailable
     * (useful for unit-testing widgets outside a full Laravel boot).
     *
     * @param  array<string, mixed>  $data
     */
    protected function fallbackRender(array $data): string
    {
        $title = (string) ($data['title'] ?? $this->name);

        return '<section data-widget="'.e(static::class).'"><h2>'.e($title).'</h2></section>';
    }

    /**
     * @return array<int, string>
     */
    public function fieldNames(): array
    {
        return array_map(static fn ($f) => $f['name'], $this->fields);
    }
}

if (! function_exists('Capell\\Themes\\Agency\\Widgets\\e')) {
    /**
     * Tiny HTML escaper fallback used by widget fallback render only.
     */
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
