<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Widget;

use Capell\Core\Enums\AssetComponentEnum;
use Capell\Core\Models;
use Capell\Frontend\Facades\Frontend;
use Capell\Layout\Models\Widget;
use Closure;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Drawer\Utils;
use stdClass;

/**
 * @property-read Models\Widget $widget
 */
abstract class AbstractWidget extends Component
{
    public array $container;

    public string $containerKey;

    public ?stdClass $loop = null;

    public int $occurrence;

    public array $widgetData = [];

    protected string $defaultView = 'capell::components.widget.default';

    protected $skipRender = false;

    abstract protected function mountWidget(): void;

    public function hydrate(): void
    {
        $this->initializeWidget();
    }

    public function mount(
        string $containerKey,
        stdClass $loop,
        array $widgetData,
    ): void {
        $this->containerKey = $containerKey;
        $this->widgetData = $widgetData;
        $this->occurrence = $widgetData['occurrence'] ?? 1;
        $this->loop = $loop;

        $this->initializeWidget();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render(array $data = [])
    {
        if ($this->skipRender) {
            return Utils::insertAttributesIntoHtmlRoot('<div></div>', [
                'wire:id' => $this->getId(),
            ]);
        }

        $data = array_merge([
            'containerKey' => $this->containerKey,
            'component_item' => $this->getComponentItem(),
            'index' => $this->loop->index,
            'language' => Frontend::getLanguage(),
            'pageRecord' => Frontend::getPage(),
            'pageParams' => Frontend::getPageParams(),
            'site' => Frontend::getSite(),
            'theme' => Frontend::getTheme(),
            'widget' => $this->widget,
            'widgetData' => $this->widgetData,
        ], $data);

        return view($this->getComponent(), $data);
    }

    #[Computed]
    public function widget(): Widget
    {
        return once(fn () => Widget::firstWhere('key', $this->widgetData['widget_key']));
    }

    protected function getComponent(): string
    {
        return $this->widget->meta['file_view'] ?? $this->widget->type->meta['file_view'] ?? $this->defaultView;
    }

    protected function getComponentItem(): string
    {
        return $this->widget->meta['component_item'] ?? $this->widget->type->meta['component_item'] ?? $this->getDefaultComponentItem();
    }

    protected function getDefaultComponentItem(): string
    {
        return AssetComponentEnum::Card->value;
    }

    protected function initializeWidget(): void
    {
        $this->mountWidget();
    }
}
