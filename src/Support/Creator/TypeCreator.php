<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\ContentSections\Models\Section;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Enums\AssetComponentEnum as CapellAssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\ContentTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeGroupEnum;
use Exception;
use Illuminate\Database\Eloquent\Model;

class TypeCreator
{
    private const string CONTENT_SECTIONS_MODEL = Section::class;

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
            case LayoutTypeEnum::Widget->value:
                $this->defaultWidgetType();
                break;
            default:
                throw new Exception('Invalid page type key: ' . $key);
        }
    }

    public function createDefaultContentType(): void
    {
        $this->ensureSectionPageTypeRegistered();

        $this->typeModel::query()->firstOrCreate([
            'default' => true,
            'type' => 'section',
        ], [
            'name' => __('capell-admin::generic.default'),
            'key' => ContentTypeEnum::Default->value,
            'admin' => [
                'type_configurator' => 'content-type',
                'notes' => __('capell-layout-builder::type.default_content_description'),
            ],
        ]);
    }

    public function createBuilderContentType(): void
    {
        $this->ensureSectionPageTypeRegistered();

        $this->typeModel::query()->firstOrCreate([
            'key' => ContentTypeEnum::Builder->value,
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
        $this->kitchenSinkReferenceWidgetType();
    }

    public function defaultWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'type' => LayoutTypeEnum::Widget->value,
            'key' => 'default',
        ], [
            'default' => true,
            'name' => __('capell-admin::generic.default'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-puzzle-piece',
                'notes' => __('capell-layout-builder::type.default_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function contentBuilderWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::SectionBuilder->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-puzzle-piece',
                'notes' => __('capell-layout-builder::type.section_builder_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'content_structure' => ContentStructure::Blocks,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function mediaWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Media->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.media'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'configurator' => 'Assets',
                'icon' => config('capell-admin.assets.media.icon'),
                'asset_types' => ['section'],
                'notes' => __('capell-layout-builder::type.media_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::AssetMedia,
                'component_item' => CapellAssetComponentEnum::Media,
            ],
        ]);
    }

    public function navigationWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Navigation->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.navigation'),
            'group' => WidgetTypeGroupEnum::Page,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Navigation',
                'icon' => 'heroicon-o-clipboard-document-list',
                'notes' => __('capell-layout-builder::type.navigation_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Navigation,
            ],
        ]);
    }

    public function pageContentWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::PageContents->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'group' => WidgetTypeGroupEnum::Page,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'PageContent',
                'layout_widget_configurator' => 'Page',
                'icon' => 'heroicon-o-document-text',
                'notes' => __('capell-layout-builder::type.page_content_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'with_next_prev' => true,
            ],
        ]);
    }

    public function resultsWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Results->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.results'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Results',
                'layout_widget_configurator' => 'Results',
                'icon' => 'heroicon-o-list-bullet',
                'notes' => __('capell-layout-builder::type.results_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::PageLatest,
            ],
        ]);
    }

    public function pagesWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Pages->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.pages'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-document-text',
                'asset_types' => [AssetEnum::Page],
                'notes' => __('capell-layout-builder::type.pages_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
            ],
        ]);
    }

    public function assetsWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Assets->value,
            'type' => LayoutTypeEnum::Widget->value,
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
                'notes' => __('capell-layout-builder::type.assets_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
            ],
        ]);
    }

    public function systemWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::System->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.system'),
            'group' => WidgetTypeGroupEnum::System,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'System',
                'layout_widget_configurator' => 'Default',
                'icon' => 'heroicon-o-wrench',
                'notes' => __('capell-layout-builder::type.system_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function contentsWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Sections->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => ['section'],
                'notes' => __('capell-layout-builder::type.sections_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'component_item' => CapellAssetComponentEnum::Card,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function heroWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Hero->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.hero_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-rocket-launch',
                'notes' => __('capell-layout-builder::type.hero_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function heroBannerWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::HeroBanner->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.hero_banner_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-flag',
                'notes' => __('capell-layout-builder::type.hero_banner_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function cardGridWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::CardGrid->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.card_grid_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-square-3-stack-3d',
                'notes' => __('capell-layout-builder::type.card_grid_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function featureListWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::FeatureList->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.feature_list_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-list-bullet',
                'notes' => __('capell-layout-builder::type.feature_list_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function ctaSectionWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::CTASection->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.call_to_action_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-megaphone',
                'notes' => __('capell-layout-builder::type.call_to_action_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function imageGalleryWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::ImageGallery->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.image_gallery_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-photo',
                'notes' => __('capell-layout-builder::type.image_gallery_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }

    public function kitchenSinkReferenceWidgetType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::KitchenSinkReference->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.kitchen_sink_reference_widget_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'KitchenSinkReference',
                'icon' => 'heroicon-o-beaker',
                'notes' => __('capell-layout-builder::type.kitchen_sink_reference_widget_description'),
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    private function ensureSectionPageTypeRegistered(): void
    {
        if (CapellCore::hasPageType('section')) {
            return;
        }

        CapellCore::registerPageType(new PageTypeData(
            name: 'section',
            model: $this->sectionModelClass(),
            label: 'Section',
        ));
    }

    /**
     * @return class-string<Model>
     */
    private function sectionModelClass(): string
    {
        $contentSectionsModel = self::CONTENT_SECTIONS_MODEL;

        if (class_exists($contentSectionsModel)) {
            return $contentSectionsModel;
        }

        return Page::class;
    }
}
