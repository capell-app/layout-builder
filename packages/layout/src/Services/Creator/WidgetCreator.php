<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Layout\Models\Widget;
use Illuminate\Support\Collection;

class WidgetCreator
{
    /**
     * @var class-string<Type>
     */
    private readonly string $typeModel;

    /**
     * @var class-string<Widget>
     */
    private readonly string $widgetModel;

    public function __construct()
    {
        $this->widgetModel = CapellCore::getModel(ModelEnum::Widget->name);
        $this->typeModel = CapellCore::getModel(CoreModelEnum::Type);
    }

    public function createWidgets(Collection $languages): void
    {
        $contentsWidgetType = $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::Contents, 'type' => LayoutTypeEnum::Widget]);
        $mediaWidgetType = $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::Media, 'type' => LayoutTypeEnum::Widget]);
        $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::Navigation, 'type' => LayoutTypeEnum::Widget]);
        $pageContentWidgetType = $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::PageContents, 'type' => LayoutTypeEnum::Widget]);
        $pageResultsWidgetType = $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::PageResults, 'type' => LayoutTypeEnum::Widget]);
        $pagesWidgetType = $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::Pages, 'type' => LayoutTypeEnum::Widget]);
        $systemWidgetType = $this->typeModel::query()->firstWhere(['key' => WidgetTypeEnum::System, 'type' => LayoutTypeEnum::Widget]);

        $this->breadcrumbWidget($systemWidgetType);
        $this->childrenWidget($pageResultsWidgetType, $languages);
        $this->contentsWidgets($contentsWidgetType);
        $this->galleryWidget($mediaWidgetType, $languages);
        $this->latestPagesWidget($pageResultsWidgetType, $languages);
        $this->mediaCarouselWidget($mediaWidgetType);
        $this->pageContentWidget($pageContentWidgetType);
        $this->pageSlotWidget($systemWidgetType);
        $this->pagesCardWidget($pagesWidgetType);
        $this->siblingsWidget($pageResultsWidgetType, $languages);
    }

    private function breadcrumbWidget(Type $systemWidgetType): void
    {
        $this->widgetModel::query()->firstOrCreate([
            'key' => 'breadcrumbs',
        ], [
            'name' => __('capell-admin::generic.breadcrumbs'),
            'type_id' => $systemWidgetType->id,
            'meta' => [
                'component' => WidgetComponentEnum::Breadcrumbs,
            ],
        ]);
    }

    private function childrenWidget(Type $pageResultsWidgetType, Collection $languages): void
    {
        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'children',
        ], [
            'name' => __('capell-admin::generic.page_children'),
            'type_id' => $pageResultsWidgetType->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageChildren,
                'with_children_count' => true,
                'with_summary' => true,
                'with_image' => true,
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'icon' => 'heroicon-c-users',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout::heading.page_children'),
            ]);
        });
    }

    private function contentsWidgets(Type $contentsWidgetType): void
    {
        $this->widgetModel::query()->firstOrCreate([
            'key' => 'contents-assets',
        ], [
            'name' => __('capell-admin::generic.contents'),
            'type_id' => $contentsWidgetType->id,
            'meta' => [
                'limit' => 6,
                'pagination' => false,
                'with_summary' => true,
                'with_link_text' => true,
                'with_image' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-o-rectangle-stack',
            ],
        ]);
    }

    private function galleryWidget(Type $mediaWidgetType, Collection $languages): void
    {
        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'gallery',
        ], [
            'name' => __('capell-admin::generic.gallery'),
            'type_id' => $mediaWidgetType->id,
            'meta' => [
                'widget_theme' => 'masonry',
                'spacing' => 'md',
                'margin' => ['lg'],
                'container' => 'full',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout::heading.gallery'),
            ]);
        });
    }

    private function latestPagesWidget(Type $pageResultsWidgetType, Collection $languages): void
    {
        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'latest-pages',
        ], [
            'name' => __('capell-admin::generic.latest_pages'),
            'type_id' => $pageResultsWidgetType->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageLatest,
                'limit' => 6,
                'pagination' => false,
                'with_summary' => false,
                'with_link_text' => true,
                'with_image' => true,
                'with_date' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-o-rectangle-stack',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-admin::heading.latest_pages'),
            ]);
        });
    }

    private function mediaCarouselWidget(Type $mediaWidgetType): void
    {
        $this->widgetModel::query()->firstOrCreate([
            'key' => 'media-carousel',
        ], [
            'name' => __('capell-admin::generic.media_carousel'),
            'type_id' => $mediaWidgetType->id,
            'meta' => [
                'component' => WidgetComponentEnum::AssetCarousel->value,
                'limit' => 20,
                'container' => 'full',
                'background_color' => 'light-gray',
                'margin' => 0,
                'padding' => ['md'],
            ],
            'admin' => [
                'schema' => CarouselWidgetSchema::getKey(),
            ],
        ]);
    }

    private function pageContentWidget(Type $pageContentWidgetType): void
    {
        $this->widgetModel::query()->firstOrCreate([
            'key' => 'page-content',
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'type_id' => $pageContentWidgetType->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageContent,
                'margin' => ['t-lg'],
                'page_content' => ['title', 'content'],
            ],
        ]);
    }

    private function pagesCardWidget(Type $pagesWidgetType): void
    {
        $this->widgetModel::query()->firstOrCreate([
            'key' => 'pages-card',
        ], [
            'name' => __('capell-admin::generic.pages_card'),
            'type_id' => $pagesWidgetType->id,
            'meta' => [
                'limit' => 10,
                'with_image' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'spacing' => 'lg',
                'margin' => ['lg'],
            ],
        ]);
    }

    private function pageSlotWidget(Type $systemWidgetType): void
    {
        $this->widgetModel::query()->firstOrCreate([
            'key' => 'page-slot',
        ], [
            'name' => __('capell-admin::generic.page_slot'),
            'type_id' => $systemWidgetType->id,
            'meta' => [
                'component' => 'capell-layout::widget.slot',
                'type' => 'slot',
            ],
        ]);
    }

    private function siblingsWidget(Type $pageResultsWidgetType, Collection $languages): void
    {
        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'siblings',
        ], [
            'name' => __('capell-admin::generic.page_siblings'),
            'type_id' => $pageResultsWidgetType->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageSiblings,
                'with_children_count' => true,
                'with_summary' => true,
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'icon' => 'heroicon-c-user-group',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout::heading.page_siblings'),
            ]);
        });
    }
}
