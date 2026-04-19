<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Widgets;

use Throwable;

/**
 * Base class shared by all theme widgets across Corporate, Agency, and SaaS.
 *
 * Mosaic is optional — widgets do not extend any Mosaic class. If Mosaic
 * is present the ServiceProvider registers these widgets via duck-typing.
 */
abstract class AbstractThemeWidget
{
    public string $name;

    public string $description;

    public string $view;

    public string $icon = 'heroicon-o-square-3-stack-3d';

    /**
     * @var array<int, array{name: string, label: string, type: string, default?: mixed, options?: array<string, string>, required?: bool}>
     */
    public array $fields = [];

    public static function make(): self
    {
        return new self;
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
                return view($this->view, $data)->render();
            } catch (Throwable) {
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
     * @return array<int, string>
     */
    public function fieldNames(): array
    {
        return array_map(static fn (array $field): string => $field['name'], $this->fields);
    }

    /**
     * Minimal fallback HTML when Laravel view factory is unavailable
     * (useful for unit-testing widgets outside a full Laravel boot).
     *
     * @param  array<string, mixed>  $data
     */
    protected function fallbackRender(array $data): string
    {
        $title = htmlspecialchars((string) ($data['title'] ?? $this->name), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $class = htmlspecialchars(static::class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<section data-widget="' . $class . '"><h2>' . $title . '</h2></section>';
    }
}
