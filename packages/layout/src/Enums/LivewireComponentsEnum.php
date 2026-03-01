<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\Layout\Livewire\Assets\Table\ContentAssets;
use Capell\Layout\Livewire\Assets\Table\PageAssets;
use Capell\Layout\Livewire\Layout\Builder;
use Capell\Layout\Livewire\Layout\WidgetTableSelect;
use Capell\Layout\Livewire\Widget\Pages;

enum LivewireComponentsEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(Builder::class)]
    case LayoutBuilder = 'capell-layout::layout.builder';

    #[Component(WidgetTableSelect::class)]
    case WidgetTableSelect = 'capell-layout::layout.widget-table-select';

    #[Component(PageAssets::class)]
    case PageAssetsTable = 'capell-layout::assets.table.page-assets';

    #[Component(ContentAssets::class)]
    case ContentAssetsTable = 'capell-layout::assets.table.content-assets';

    #[Component(Pages::class)]
    case PagesWidget = 'capell-layout::widget.pages';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }

    public static function loadAssetComponent(string $assetType): self
    {
        return match ($assetType) {
            'page' => self::PageAssetsTable,
            'content' => self::ContentAssetsTable,
        };
    }

    public function getComponent(): ?string
    {
        return $this->getCaseAttribute(Component::class)?->class;
    }
}
