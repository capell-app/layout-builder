<?php

declare(strict_types=1);

namespace Capell\Mosaic\Support\Creator;

use Capell\Core\Enums\AssetComponentEnum as CapellAssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Mosaic\Enums\ContentTypeEnum;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Enums\WidgetTypeEnum;
use Capell\Mosaic\Enums\WidgetTypeGroupEnum;
use Capell\Mosaic\Filament\Schemas\Layouts\Widgets\DefaultLayoutWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Layouts\Widgets\PageLayoutWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Layouts\Widgets\ResultsLayoutWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Types\ContentTypeSchema;
use Capell\Mosaic\Filament\Schemas\Types\WidgetTypeSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\AssetsWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\NavigationWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\PageContentWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\ResultsWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\SystemWidgetSchema;
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
            case LayoutTypeEnum::Section->value:
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
            'type' => LayoutTypeEnum::Section,
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
            'type' => LayoutTypeEnum::Section,
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
            'key' => WidgetTypeEnum::SectionBuilder,
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
                'asset_types' => [LayoutAssetEnum::Section],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'component_item' => CapellAssetComponentEnum::Media,
                'view_file' => 'capell-mosaic::components.widget.asset.media',
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
                'layout_widget_schema' => PageLayoutWidgetSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => ResultsWidgetSchema::getKey(),
                'layout_widget_schema' => ResultsLayoutWidgetSchema::getKey(),
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
                    LayoutAssetEnum::Section,
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
                'layout_widget_schema' => DefaultLayoutWidgetSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-rectangle-stack',
                'asset_types' => [LayoutAssetEnum::Section],
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
                'type_schema' => WidgetTypeSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
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
                'type_schema' => WidgetTypeSchema::getKey(),
                'icon' => 'heroicon-o-photo',
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Default,
            ],
        ]);
    }
}
