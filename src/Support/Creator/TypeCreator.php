<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Enums\AssetComponentEnum as CapellAssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Enums\ContentTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeGroupEnum;
use Exception;

class TypeCreator
{
    /**
     * @var class-string<Type>
     */
    public string $typeModel = Type::class;

    public function create(string $key): void
    {
        switch ($key) {
            case 'section':
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
            'type' => 'section',
        ], [
            'name' => __('capell-admin::generic.default'),
            'key' => ContentTypeEnum::Default,
            'admin' => [
                'type_configurator' => 'content-type',
            ],
        ]);
    }

    public function createBuilderContentType(): void
    {
        $this->typeModel::query()->firstOrCreate([
            'key' => ContentTypeEnum::Builder,
            'type' => 'section',
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_configurator' => 'content-type',
            ],
            'meta' => [

                'content_structure' => ContentStructure::Blocks,
            ],
        ]);
    }

    public function createWidgetTypes(): void
    {
        $this->defaultWidgetType();
        $this->contentsWidgetType();
        $this->contentBuilderWidgetType();
        $this->mediaWidgetType();
        $this->navigationWidgetType();
        $this->pageContentWidgetType();
        $this->resultsWidgetType();
        $this->pagesWidgetType();
        $this->assetsWidgetType();
        $this->systemWidgetType();
        $this->heroWidgetType();
        $this->heroBannerWidgetType();
        $this->cardGridWidgetType();
        $this->featureListWidgetType();
        $this->ctaSectionWidgetType();
        $this->imageGalleryWidgetType();
    }

    public function defaultWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'type' => LayoutTypeEnum::Widget,
            'key' => 'default',
            'default' => true,
        ], [
            'name' => __('capell-admin::generic.default'),
            'admin' => [
                'type_configurator' => 'Widget',
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
            'key' => WidgetTypeEnum::SectionBuilder,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_configurator' => 'Widget',
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
                'configurator' => 'Assets',
                'icon' => config('capell-admin.assets.media.icon'),
                'asset_types' => ['section'],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::AssetMedia,
                'component_item' => CapellAssetComponentEnum::Media,
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
                'type_configurator' => 'Widget',
                'configurator' => 'Navigation',
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
                'type_configurator' => 'Widget',
                'configurator' => 'PageContent',
                'layout_widget_configurator' => 'Page',
                'icon' => 'heroicon-o-document-text',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'with_next_prev' => true,
            ],
        ]);
    }

    public function resultsWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Results,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.results'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Results',
                'layout_widget_configurator' => 'Results',
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
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
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
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [
                    AssetEnum::Page,
                    'section',
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
                'type_configurator' => 'Widget',
                'configurator' => 'System',
                'layout_widget_configurator' => 'Default',
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
            'key' => WidgetTypeEnum::Sections,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => ['section'],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'component_item' => CapellAssetComponentEnum::Card,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function heroWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Hero,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'Hero',
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-rocket-launch',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function heroBannerWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::HeroBanner,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'Hero Banner',
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-flag',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function cardGridWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::CardGrid,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'Card Grid',
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-square-3-stack-3d',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function featureListWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::FeatureList,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'Feature List',
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-list-bullet',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function ctaSectionWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::CTASection,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'CTA Section',
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-megaphone',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function imageGalleryWidgetType(): Type
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::ImageGallery,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => 'Image Gallery',
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-photo',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }
}
