<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Enums\ContentEditorEnum;
use Capell\Core\Enums\AssetComponentEnum as CapellAssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\AssetComponentEnum;
use Capell\Layout\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Enums\WidgetTypeGroupEnum;
use Capell\Layout\Filament\Schemas\LayoutWidget\DefaultLayoutWidgetSchema;
use Capell\Layout\Filament\Schemas\LayoutWidget\PageLayoutWidgetSchema;
use Capell\Layout\Filament\Schemas\Type\WidgetTypeSchema;
use Capell\Layout\Filament\Schemas\Widget\AssetsWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\MediaWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\NavigationWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\PageContentWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\ResultsWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\SystemWidgetSchema;

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
                'type_schema' => WidgetTypeSchema::getKey(),
                'icon' => 'heroicon-o-puzzle-piece',
                'content_editor' => ContentEditorEnum::ContentBuilder->value,
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
            'group' => WidgetTypeGroupEnum::Asset->value,
            'admin' => [
                'schema' => MediaWidgetSchema::getKey(),
                'icon' => CapellCore::getAsset(AssetEnum::Media->name)->getIcon(),
                'asset_types' => [AssetEnum::Media->value],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'component_item' => CapellAssetComponentEnum::Media,
                'view_file' => 'capell-layout::components.widget.assets.media',
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
            'group' => WidgetTypeGroupEnum::Page->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => NavigationWidgetSchema::getKey(),
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
            'group' => WidgetTypeGroupEnum::Page->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => PageContentWidgetSchema::getKey(),
                'layout_container_widget_schema' => PageLayoutWidgetSchema::getKey(),
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
            'group' => WidgetTypeGroupEnum::Asset->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => ResultsWidgetSchema::getKey(),
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
            'group' => WidgetTypeGroupEnum::Asset->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-document-text',
                'asset_types' => [AssetEnum::Page->value],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
            ],
        ]);
    }

    public function assetsWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Assets->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.assets'),
            'group' => WidgetTypeGroupEnum::Asset->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [
                    AssetEnum::Page->value,
                    AssetEnum::Media->value,
                    LayoutAssetEnum::Content->value,
                ],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
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
            'group' => WidgetTypeGroupEnum::System->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => SystemWidgetSchema::getKey(),
                'layout_container_widget_schema' => DefaultLayoutWidgetSchema::getKey(),
                'icon' => 'heroicon-o-wrench',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function contentsWidgetType(): Type
    {
        return $this->typeModel::firstOrCreate([
            'key' => WidgetTypeEnum::Contents->value,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => WidgetTypeGroupEnum::Asset->value,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [LayoutAssetEnum::Content->value],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'component_item' => AssetComponentEnum::Content->value,
                'margin' => ['lg'],
            ],
        ]);
    }
}
