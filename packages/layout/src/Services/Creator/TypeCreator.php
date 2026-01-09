<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Enums\AssetComponentEnum as CapellAssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\AssetComponentEnum;
use Capell\Layout\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Layout\Enums\ContentTypeEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Enums\WidgetTypeGroupEnum;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\DefaultLayoutWidgetSchema;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\PageLayoutWidgetSchema;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\ContentTypeSchema;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\NavigationWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\PageContentWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\ResultsWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\SystemWidgetSchema;
use Exception;

class TypeCreator
{
    /**
     * @var class-string<Type>
     */
    public string $typeModel;

    public function __construct()
    {
        $this->typeModel = CapellCore::getModel(ModelEnum::Type);
    }

    public function create(string $key): void
    {
        switch ($key) {
            case LayoutTypeEnum::Content->value:
                $this->createDefaultContentType();
                $this->createBuilderContentType();
                break;
            case LayoutTypeEnum::Widget->value:
                $this->defaultWidgetType();
                break;
            default:
                throw new Exception('Invalid page type key: ' . $key);
        }
    }

    public function createDefaultContentType(): void
    {
        $this->typeModel::query()->firstOrCreate([
            'default' => true,
            'type' => LayoutTypeEnum::Content,
        ], [
            'name' => __('capell-admin::generic.default'),
            'key' => ContentTypeEnum::Default,
            'admin' => [
                'type_schema' => ContentTypeSchema::getKey(),
            ],
        ]);
    }

    public function createBuilderContentType(): void
    {
        $this->typeModel::query()->firstOrCreate([
            'key' => ContentTypeEnum::Builder,
            'type' => LayoutTypeEnum::Content,
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_schema' => ContentTypeSchema::getKey(),
            ],
            'meta' => [

                'content_structure' => ContentStructure::Blocks,
            ],
        ]);
    }

    public function createWidgetTypes(): void
    {
        $this->contentsWidgetType();
        $this->defaultWidgetType();
        $this->contentBuilderWidgetType();
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
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Default,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.default'),
            'default' => true,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'icon' => 'heroicon-o-puzzle-piece',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function contentBuilderWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::ContentBuilder,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'icon' => 'heroicon-o-puzzle-piece',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'content_structure' => ContentStructure::Blocks,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function mediaWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Media,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.media'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => config('capell-admin.assets.media.icon'),
                'asset_types' => [\Capell\Layout\Enums\AssetEnum::Content],
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
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Navigation,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.navigation'),
            'group' => WidgetTypeGroupEnum::Page,
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
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::PageContents,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'group' => WidgetTypeGroupEnum::Page,
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
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::PageResults,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.page_results'),
            'group' => WidgetTypeGroupEnum::Asset,
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
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Pages,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.pages'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-document-text',
                'asset_types' => [AssetEnum::Page],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
            ],
        ]);
    }

    public function assetsWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Assets,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.assets'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [
                    AssetEnum::Page,
                    LayoutAssetEnum::Content,
                ],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
            ],
        ]);
    }

    public function systemWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::System,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.system'),
            'group' => WidgetTypeGroupEnum::System,
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
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Contents,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [LayoutAssetEnum::Content],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'component_item' => AssetComponentEnum::Content,
                'margin' => ['lg'],
            ],
        ]);
    }
}
