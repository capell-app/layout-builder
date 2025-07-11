<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

use Capell\Layout\Models;
use Closure;
use Illuminate\View\Component;
use stdClass;

abstract class AbstractWidget extends Component
{
    protected static string $defaultView = 'capell-layout::components.widget.default';

    protected bool $skipRender = false;

    public function __construct(
        public array $container,
        public string $containerKey,
        public int $widgetIndex,
        public stdClass $loop,
        public Models\Widget $widget,
        public array $widgetData = [],
    ) {
        $this->mountWidget();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render(array $data = [])
    {
        if ($this->skipRender) {
            return '';
        }

        if (! empty($this->widget->meta['view_file'])) {
            $component = $this->widget->meta['view_file'];
        } elseif (! empty($this->widget->type->meta['view_file'])) {
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
