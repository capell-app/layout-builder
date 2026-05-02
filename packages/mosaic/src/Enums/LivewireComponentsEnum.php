<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\Mosaic\Livewire\Assets\Table\PageAssets;
use Capell\Mosaic\Livewire\Assets\Table\SectionAssets;
use Capell\Mosaic\Livewire\Filament\LayoutBuilder;
use Capell\Mosaic\Livewire\Widget\Pages;

enum LivewireComponentsEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(LayoutBuilder::class)]
    case LayoutBuilder = 'capell-mosaic::filament.layout-builder';

    #[Component(PageAssets::class)]
    case PageAssetsTable = 'capell-mosaic::assets.table.page-assets';

    #[Component(SectionAssets::class)]
    case ContentAssetsTable = 'capell-mosaic::assets.table.section-assets';

    #[Component(Pages::class)]
    case PagesWidget = 'capell-mosaic::widget.pages';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }

    public function getComponent(): ?string
    {
        return $this->getCaseAttribute(Component::class)?->class;
    }
}
