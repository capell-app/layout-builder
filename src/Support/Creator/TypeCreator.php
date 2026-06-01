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
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\BlockTypeEnum;
use Capell\LayoutBuilder\Enums\BlockTypeGroupEnum;
use Capell\LayoutBuilder\Enums\ContentTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
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
                $this->defaultBlockType();
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

    public function createBlockTypes(): void
    {
        $this->defaultBlockType();
        $this->contentsBlockType();
        $this->contentBuilderBlockType();
        $this->mediaBlockType();
        $this->navigationBlockType();
        $this->pageContentBlockType();
        $this->resultsBlockType();
        $this->pagesBlockType();
        $this->assetsBlockType();
        $this->systemBlockType();
        $this->heroBlockType();
        $this->heroBannerBlockType();
        $this->cardGridBlockType();
        $this->featureListBlockType();
        $this->ctaSectionBlockType();
        $this->imageGalleryBlockType();
        $this->kitchenSinkReferenceBlockType();
    }

    public function defaultBlockType(): Blueprint
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
                'notes' => __('capell-layout-builder::type.default_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function contentBuilderBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::SectionBuilder->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.contents_builder'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-puzzle-piece',
                'notes' => __('capell-layout-builder::type.section_builder_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
                'content_structure' => ContentStructure::Blocks,
                'padding' => ['lg'],
            ],
        ]);
    }

    public function mediaBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Media->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.media'),
            'group' => BlockTypeGroupEnum::Asset,
            'admin' => [
                'configurator' => 'Assets',
                'icon' => config('capell-admin.assets.media.icon'),
                'asset_types' => ['section'],
                'notes' => __('capell-layout-builder::type.media_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::AssetMedia,
                'component_item' => CapellAssetComponentEnum::Media,
            ],
        ]);
    }

    public function navigationBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Navigation->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.navigation'),
            'group' => BlockTypeGroupEnum::Page,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Navigation',
                'icon' => 'heroicon-o-clipboard-document-list',
                'notes' => __('capell-layout-builder::type.navigation_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Navigation,
            ],
        ]);
    }

    public function pageContentBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::PageContents->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'group' => BlockTypeGroupEnum::Page,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'PageContent',
                'layout_block_configurator' => 'Page',
                'icon' => 'heroicon-o-document-text',
                'notes' => __('capell-layout-builder::type.page_content_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
                'with_next_prev' => true,
            ],
        ]);
    }

    public function resultsBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Results->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.results'),
            'group' => BlockTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Results',
                'layout_block_configurator' => 'Results',
                'icon' => 'heroicon-o-list-bullet',
                'notes' => __('capell-layout-builder::type.results_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::PageLatest,
            ],
        ]);
    }

    public function pagesBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Pages->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.pages'),
            'group' => BlockTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-document-text',
                'asset_types' => [AssetEnum::Page],
                'notes' => __('capell-layout-builder::type.pages_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Assets,
            ],
        ]);
    }

    public function assetsBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Assets->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.assets'),
            'group' => BlockTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [
                    AssetEnum::Page,
                    'section',
                ],
                'notes' => __('capell-layout-builder::type.assets_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Assets,
            ],
        ]);
    }

    public function systemBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::System->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.system'),
            'group' => BlockTypeGroupEnum::System,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'System',
                'layout_block_configurator' => 'Default',
                'icon' => 'heroicon-o-wrench',
                'notes' => __('capell-layout-builder::type.system_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function contentsBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Sections->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-admin::generic.contents'),
            'group' => BlockTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => ['section'],
                'notes' => __('capell-layout-builder::type.sections_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Assets,
                'component_item' => CapellAssetComponentEnum::Card,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function heroBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::Hero->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.hero_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-rocket-launch',
                'notes' => __('capell-layout-builder::type.hero_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function heroBannerBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::HeroBanner->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.hero_banner_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-flag',
                'notes' => __('capell-layout-builder::type.hero_banner_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function cardGridBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::CardGrid->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.card_grid_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-square-3-stack-3d',
                'notes' => __('capell-layout-builder::type.card_grid_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function featureListBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::FeatureList->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.feature_list_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-list-bullet',
                'notes' => __('capell-layout-builder::type.feature_list_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function ctaSectionBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::CTASection->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.call_to_action_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-megaphone',
                'notes' => __('capell-layout-builder::type.call_to_action_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function imageGalleryBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::ImageGallery->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.image_gallery_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'icon' => 'heroicon-o-photo',
                'notes' => __('capell-layout-builder::type.image_gallery_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
            ],
        ]);
    }

    public function kitchenSinkReferenceBlockType(): Blueprint
    {
        return $this->typeModel::query()->firstOrCreate([
            'key' => BlockTypeEnum::KitchenSinkReference->value,
            'type' => LayoutTypeEnum::Widget->value,
        ], [
            'name' => __('capell-layout-builder::type.kitchen_sink_reference_block_name'),
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'KitchenSinkReference',
                'icon' => 'heroicon-o-beaker',
                'notes' => __('capell-layout-builder::type.kitchen_sink_reference_block_description'),
            ],
            'meta' => [
                'component' => BlockComponentEnum::Default,
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
