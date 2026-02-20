<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\Layout\Filament\Resources\Pages\RelationManagers\ContentsRelationManager;
use Capell\Layout\Filament\Resources\Widgets\RelationManagers\WidgetAssetsRelationManager;
use Capell\Layout\Livewire\Assets\Table\ContentAssetsTable;
use Capell\Layout\Livewire\Assets\Table\PageAssetsTable;
use Capell\Layout\Livewire\Layout\WidgetTableSelect;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Livewire\Widget\PagesWidget;

enum LivewireComponentsEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(LayoutBuilder::class)]
    case LayoutBuilder = 'capell.layout.livewire.layout-builder';

    #[Component(ContentsRelationManager::class)]
    case ContentsRelationManager = 'capell.layout.filament.resources.page-resource.relation-managers.contents-relation-manager';

    #[Component(WidgetAssetsRelationManager::class)]
    case WidgetAssetsRelationManager = 'capell.layout.filament.resources.widget-resource.relation-managers.widget-assets-relation-manager';

    #[Component(WidgetTableSelect::class)]
    case WidgetTableSelect = 'capell.layout.livewire.layout.widget-table-select';

    #[Component(PageAssetsTable::class)]
    case PageAssetsTable = 'capell.layout.livewire.assets.table.page';

    #[Component(ContentAssetsTable::class)]
    case ContentAssetsTable = 'capell.layout.livewire.assets.table.content';

    #[Component(PagesWidget::class)]
    case PagesWidget = 'capell.layout.livewire.widget.pages';

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
