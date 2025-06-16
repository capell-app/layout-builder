<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Enums\ResourceComponentEnum as CapellResourceComponentEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ResourceComponentEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Schemas;

class WidgetTypeCreator
{
    /**
     * @var class-string<Type>
     */
    public string $typeModel;

    public function __construct()
    {
        $this->typeModel = CapellCore::getModel(ModelEnum::Type);
    }

    public function contentsWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Contents->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => 'assets',
            'admin' => [
                'schema' => Schemas\Widget\AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => ['content'],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Resources,
                'component_item' => ResourceComponentEnum::Content->value,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function createWidgetTypes(): void
    {
        $this->contentsWidgetType();
        $this->defaultWidgetType();
        $this->mediaWidgetType();
        $this->navigationWidgetType();
        $this->pageContentWidgetType();
        $this->pageResultsWidgetType();
        $this->pagesWidgetType();
        $this->assetsWidgetType();
        $this->systemWidgetType();
    }

    public function defaultWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Default->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.default'),
            'default' => true,
            'admin' => [
                'icon' => 'heroicon-o-puzzle-piece',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function mediaWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Media->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.media'),
            'group' => 'assets',
            'admin' => [
                'schema' => Schemas\Widget\MediaWidgetSchema::getKey(),
                'icon' => CapellAdmin::getAsset('media')->getIcon(),
                'asset_types' => ['media'],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Resources,
                'component_item' => CapellResourceComponentEnum::Media,
                'file_view' => 'capell::components.widget.assets.media.index',
            ],
        ]);
    }

    public function navigationWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Navigation->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.navigation'),
            'group' => 'pages',
            'admin' => [
                'schema' => Schemas\Widget\NavigationWidgetSchema::getKey(),
                'icon' => 'heroicon-o-clipboard-document-list',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Navigation,
            ],
        ]);
    }

    public function pageContentWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::PageContents->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'admin' => [
                'schema' => Schemas\Widget\PageContentWidgetSchema::getKey(),
                'layout_container_widget_schema' => Schemas\LayoutWidget\PageLayoutWidgetSchema::getKey(),
                'icon' => 'heroicon-o-document-text',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function pageResultsWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::PageResults->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.page_results'),
            'admin' => [
                'schema' => Schemas\Widget\ResultsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-list-bullet',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::PageLatest,
            ],
        ]);
    }

    public function pagesWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Pages->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.pages'),
            'group' => 'assets',
            'admin' => [
                'schema' => Schemas\Widget\AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-document-text',
                'asset_types' => ['page'],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Resources,
            ],
        ]);
    }

    public function assetsWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Assets->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.resources'),
            'group' => 'assets',
            'admin' => [
                'schema' => Schemas\Widget\AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => ['content', 'media', 'page'],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Resources,
            ],
        ]);
    }

    public function systemWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::System->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.system'),
            'group' => 'system',
            'admin' => [
                'schema' => Schemas\Widget\SystemWidgetSchema::getKey(),
                'layout_container_widget_schema' => Schemas\LayoutWidget\DefaultLayoutWidgetSchema::getKey(),
                'icon' => 'heroicon-o-wrench',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }
}
