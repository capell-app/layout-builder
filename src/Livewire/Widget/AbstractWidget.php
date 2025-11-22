<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Widget;

use Capell\Core\Enums\AssetComponentEnum;
use Capell\Frontend\Facades\FrontendLoader;
use Capell\Layout\Helpers\CapellLayoutHelper;
use Capell\Layout\Models\Widget;
use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Drawer\Utils;
use stdClass;

/**
 * @property-read Widget $widget
 */
abstract class AbstractWidget extends Component
{
    public array $container;

    public string $containerKey;

    public ?stdClass $loop = null;

    public int $occurrence;

    public array $widgetData = [];

    protected static string $defaultView = 'capell-layout::components.widget.default';

    protected $skipRender = false;

    abstract protected function mountWidget(): void;

    public static function getViewName(): string
    {
        return static::$defaultView;
    }

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

    #[Computed]
    public function widget(): Widget
    {
        return CapellLayoutHelper::getWidgetByKey($this->widgetData['widget_key']);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
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
            'language' => FrontendLoader::getLanguage(),
            'pageRecord' => FrontendLoader::getPage(),
            'pageParams' => FrontendLoader::getPageParams(),
            'site' => FrontendLoader::getSite(),
            'theme' => FrontendLoader::getTheme(),
            'widget' => $this->widget,
            'widgetData' => $this->widgetData,
        ], $data);

        return view($this->getComponent(), $data);
    }

    protected function getComponent(): string
    {
        return $this->widget->meta['view_file'] ?? $this->widget->type->meta['view_file'] ?? static::$defaultView;
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
