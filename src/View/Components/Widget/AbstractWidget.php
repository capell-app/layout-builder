<?php

declare(strict_types=1);

namespace Capell\Mosaic\View\Components\Widget;

use Capell\Mosaic\Models\Widget;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use stdClass;

abstract class AbstractWidget extends Component
{
    protected static string $defaultView = 'capell-mosaic::components.widget.default';

    protected bool $skipRender = false;

    public function __construct(
        public array $container,
        public string $containerKey,
        public int $widgetIndex,
        public stdClass $loop,
        public Widget $widget,
        public array $widgetData = [],
    ) {
        $this->mountWidget();
    }

    public function render(array $data = []): View|string|Closure
    {
        if ($this->skipRender) {
            return '';
        }

        if (isset($this->widget->meta['view_file']) && $this->widget->meta['view_file'] !== '') {
            $component = $this->widget->meta['view_file'];
        } elseif (isset($this->widget->type->meta['view_file']) && $this->widget->type->meta['view_file'] !== '') {
            $component = $this->widget->type->meta['view_file'];
        } else {
            $component = static::$defaultView;
        }

        $data['component_item'] = $this->getComponentItem();

        return view($component, $data);
    }

    protected function getComponentItem(): ?string
    {
        return $this->widget->meta['component_item'] ?? $this->widget->type->meta['component_item'] ?? null;
    }

    protected function mountWidget(): void {}
}
