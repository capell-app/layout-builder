<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Enums\AssetComponentEnum as CapellAssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\ContentTypeEnum;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Enums\ElementTypeEnum;
use Capell\LayoutBuilder\Enums\ElementTypeGroupEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Exception;

class TypeCreator
{
    /**
     * @var class-string<Blueprint>
     */
    public string $typeModel = Blueprint::class;

    public function create(string $key): void
    {
        switch ($key) {
            case 'section':
                $this->createDefaultContentType();
                $this->createBuilderContentType();
                break;
            case LayoutTypeEnum::Element->value:
                $this->defaultElementType();
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
                'notes' => __('capell-layout-builder::type.default_content_description'),
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
                'notes' => __('capell-layout-builder::type.builder_content_description'),
            ],
            'meta' => [

                'content_structure' => ContentStructure::Blocks,
            ],
        ]);
    }

    public function createElementTypes(): void
    {
        $this->defaultElementType();
        $this->contentsElementType();
        $this->contentBuilderElementType();
        $this->mediaElementType();
        $this->navigationElementType();
        $this->pageContentElementType();
        $this->resultsElementType();
        $this->pagesElementType();
        $this->assetsElementType();
        $this->systemElementType();
        $this->heroElementType();
        $this->heroBannerElementType();
        $this->cardGridElementType();
        $this->featureListElementType();
        $this->ctaSectionElementType();
        $this->imageGalleryElementType();
    }

    public function defaultElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'type' => LayoutTypeEnum::Element,
            'key' => 'default',
            'default' => true,
        ], [
            'name' => __('capell-admin::generic.default'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-puzzle-piece',
                'notes' => __('capell-layout-builder::type.default_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function contentBuilderElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::SectionBuilder,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-puzzle-piece',
                'notes' => __('capell-layout-builder::type.section_builder_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
                'content_structure' => ContentStructure::Blocks,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function mediaElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Media,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.media'),
            'group' => ElementTypeGroupEnum::Asset,
            'admin' => [
                'configurator' => 'Assets',
                'icon' => config('capell-admin.assets.media.icon'),
                'asset_types' => ['section'],
                'notes' => __('capell-layout-builder::type.media_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::AssetMedia,
                'component_item' => CapellAssetComponentEnum::Media,
            ],
        ]);
    }

    public function navigationElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Navigation,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.navigation'),
            'group' => ElementTypeGroupEnum::Page,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'Navigation',
                'icon' => 'heroicon-o-clipboard-document-list',
                'notes' => __('capell-layout-builder::type.navigation_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Navigation,
            ],
        ]);
    }

    public function pageContentElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::PageContents,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'group' => ElementTypeGroupEnum::Page,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'PageContent',
                'layout_element_configurator' => 'Page',
                'icon' => 'heroicon-o-document-text',
                'notes' => __('capell-layout-builder::type.page_content_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
                'with_next_prev' => true,
            ],
        ]);
    }

    public function resultsElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Results,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.results'),
            'group' => ElementTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'Results',
                'layout_element_configurator' => 'Results',
                'icon' => 'heroicon-o-list-bullet',
                'notes' => __('capell-layout-builder::type.results_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::PageLatest,
            ],
        ]);
    }

    public function pagesElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Pages,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.pages'),
            'group' => ElementTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-document-text',
                'asset_types' => [AssetEnum::Page],
                'notes' => __('capell-layout-builder::type.pages_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Assets,
            ],
        ]);
    }

    public function assetsElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Assets,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.assets'),
            'group' => ElementTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [
                    AssetEnum::Page,
                    'section',
                ],
                'notes' => __('capell-layout-builder::type.assets_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Assets,
            ],
        ]);
    }

    public function systemElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::System,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.system'),
            'group' => ElementTypeGroupEnum::System,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'System',
                'layout_element_configurator' => 'Default',
                'icon' => 'heroicon-o-wrench',
                'notes' => __('capell-layout-builder::type.system_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }

    public function contentsElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Sections,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => ElementTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => ['section'],
                'notes' => __('capell-layout-builder::type.sections_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Assets,
                'component_item' => CapellAssetComponentEnum::Card,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function heroElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Hero,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::type.hero_element_name'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-rocket-launch',
                'notes' => __('capell-layout-builder::type.hero_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }

    public function heroBannerElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::HeroBanner,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::type.hero_banner_element_name'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-flag',
                'notes' => __('capell-layout-builder::type.hero_banner_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }

    public function cardGridElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::CardGrid,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::type.card_grid_element_name'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-square-3-stack-3d',
                'notes' => __('capell-layout-builder::type.card_grid_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }

    public function featureListElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::FeatureList,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::type.feature_list_element_name'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-list-bullet',
                'notes' => __('capell-layout-builder::type.feature_list_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }

    public function ctaSectionElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::CTASection,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::type.call_to_action_element_name'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-megaphone',
                'notes' => __('capell-layout-builder::type.call_to_action_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }

    public function imageGalleryElementType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::ImageGallery,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::type.image_gallery_element_name'),
            'admin' => [
                'type_configurator' => 'Element',
                'icon' => 'heroicon-o-photo',
                'notes' => __('capell-layout-builder::type.image_gallery_element_description'),
            ],
            'meta' => [
                'component' => ElementComponentEnum::Default,
            ],
        ]);
    }
}
