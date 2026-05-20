<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use BackedEnum;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\RenderableTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\LayoutBuilder\Actions\InvalidateTypeLayoutPreviewImagesAction;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadResolver;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\ComponentTypeEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\LivewireComponentsEnum;
use Capell\LayoutBuilder\Listeners\AfterRecordSaved;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Listeners\LayoutSavingListener;
use Capell\LayoutBuilder\Listeners\SiteTreeRebuilt;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\DefaultLayoutInterceptor;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\HomeLayoutInterceptor;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\ResultsLayoutInterceptor;
use Capell\LayoutBuilder\Support\RenderHooks\RegisterMainContentLayoutHook;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

final class LayoutBuilderCoreRegistrar
{
    /** @var array<int, true> */
    private static array $mainContentHookRegistries = [];

    public function register(): void
    {
        LayoutModelRegistrar::register();

        $this->registerManagers();
        $this->registerRelationships();
        $this->registerModelExtensions();
        $this->registerModelEvents();
        $this->registerModelInterceptors();
        $this->registerPageTypes();
        $this->registerComponents();
        $this->registerRenderables();
        $this->registerRenderHooks();
        $this->registerListeners();
        $this->registerCloneableRelations();
    }

    private function registerManagers(): void
    {
        App::singleton(CapellLayoutManager::class, fn (): CapellLayoutManager => new CapellLayoutManager);
        App::bind(PublicBlockPayloadResolver::class, DefaultPublicBlockPayloadResolver::class);
        App::tag([], PublicBlockPayloadContributor::TAG);
    }

    private function registerModelExtensions(): void
    {
        Layout::addFillable(['blocks']);
        Layout::addCasts(['blocks' => 'array']);
    }

    private function registerRelationships(): void
    {
        Layout::resolveRelationUsing(
            'layoutBlocks',
            fn (Layout $model): BelongsToJson => $model->belongsToJson(
                Block::class,
                'blocks',
                'key',
            ),
        );

        Page::resolveRelationUsing(
            'blockAssets',
            fn (Page $model): MorphMany => $model->morphMany(BlockAsset::class, 'pageable'),
        );

        Page::resolveRelationUsing(
            'blocks',
            fn (Page $model): MorphToMany => $model->morphToMany(
                Block::class,
                'asset',
                'block_assets',
                'asset_id',
                'block_id',
            )
                ->wherePivot('asset_type', $model->getMorphClass()),
        );

        Blueprint::resolveRelationUsing(
            'blocks',
            fn (Blueprint $model): HasMany => $model->hasMany(Block::class, 'blueprint_id'),
        );
    }

    private function registerModelEvents(): void
    {
        Layout::saving(resolve(LayoutSavingListener::class));

        Blueprint::updated(function (Blueprint $type): void {
            $rawType = $type->getRawOriginal('type');

            if ($rawType !== LayoutTypeEnum::Block->value) {
                return;
            }

            if (! $type->wasChanged(['name', 'admin'])) {
                return;
            }

            InvalidateTypeLayoutPreviewImagesAction::run($type);
        });
    }

    private function registerModelInterceptors(): void
    {
        CapellCore::registerModelInterceptor(Layout::class, DefaultLayoutInterceptor::class, LayoutEnum::Default);
        CapellCore::registerModelInterceptor(Layout::class, HomeLayoutInterceptor::class, LayoutEnum::Home);
        CapellCore::registerModelInterceptor(Layout::class, ResultsLayoutInterceptor::class, LayoutEnum::Results);
    }

    private function registerPageTypes(): void
    {
        foreach (LayoutTypeEnum::cases() as $type) {
            CapellCore::registerPageType(
                new PageTypeData(
                    name: $type->value,
                    model: $type->getModel(),
                    label: $type->getLabel(),
                ),
            );
        }
    }

    private function registerComponents(): void
    {
        foreach (ComponentTypeEnum::cases() as $componentType) {
            /** @var class-string<BackedEnum> $enumClass */
            $enumClass = $componentType->value;
            CapellCore::registerComponents($componentType->name, $enumClass::cases());
        }

        CapellCore::registerComponents(ComponentTypeEnum::Asset, FrontendComponentKeyEnum::cases());
    }

    private function registerRenderables(): void
    {
        $registry = App::make(RenderableRegistry::class);

        foreach (BlockComponentEnum::cases() as $blockComponent) {
            $blade = match ($blockComponent) {
                BlockComponentEnum::AssetAccordion => 'capell::block.asset.accordion',
                BlockComponentEnum::AssetBanner => 'capell::block.asset.banners',
                BlockComponentEnum::AssetBlock => 'capell::block.asset.blocks',
                BlockComponentEnum::AssetCarousel => 'capell::block.asset.carousel',
                BlockComponentEnum::AssetFeatures => 'capell::block.asset.features',
                BlockComponentEnum::AssetMedia => 'capell::block.asset.media',
                BlockComponentEnum::AssetTestimonials => 'capell::block.asset.testimonials',
                BlockComponentEnum::AnnouncementBar => 'capell::block.announcement-bar',
                BlockComponentEnum::Assets => 'capell::block.asset',
                BlockComponentEnum::BannerImage => 'capell::block.banner-image',
                BlockComponentEnum::Default => 'capell::block.default',
                BlockComponentEnum::Hero => 'capell::block.hero',
                BlockComponentEnum::Navigation => 'capell::block.navigation',
                BlockComponentEnum::NavigationTabs => 'capell::block.navigation.tabs',
                BlockComponentEnum::PageBreadcrumbs => 'capell::block.page.breadcrumbs',
                BlockComponentEnum::PageChildren => 'capell::block.page.children',
                BlockComponentEnum::PageContent => 'capell::block.page.content',
                BlockComponentEnum::PageLatest => 'capell::block.page.latest',
                BlockComponentEnum::PageSiblings => 'capell::block.page.siblings',
                BlockComponentEnum::PageSlot => 'capell::block.slot',
                BlockComponentEnum::Pages => 'capell::block.asset.pages',
                BlockComponentEnum::Snippet => 'capell::block.snippet',
                BlockComponentEnum::ApHeroBanner => 'capell::block.modern.hero-banner',
                BlockComponentEnum::ApCardGrid => 'capell::block.modern.card-grid',
                BlockComponentEnum::ApFeatureList => 'capell::block.modern.feature-list',
                BlockComponentEnum::ApCTASection => 'capell::block.modern.cta-section',
                BlockComponentEnum::ApImageGallery => 'capell::block.modern.image-gallery',
                BlockComponentEnum::ApTeamMembers => 'capell::block.modern.team-members',
                BlockComponentEnum::ApPricingTable => 'capell::block.modern.pricing-table',
                BlockComponentEnum::ApTestimonials => 'capell::block.modern.testimonials',
                BlockComponentEnum::ApFaqSection => 'capell::block.modern.faq-section',
                BlockComponentEnum::ApStatsSection => 'capell::block.modern.stats-section',
                BlockComponentEnum::ApAlternatingContent => 'capell::block.modern.alternating-content',
                BlockComponentEnum::ApProcessSteps => 'capell::block.modern.process-steps',
            };

            $registry->register(new RenderableDefinitionData(
                key: $blockComponent->value,
                type: 'layout-block',
                blade: $blade,
            ));
        }

        foreach (FrontendComponentKeyEnum::cases() as $assetComponent) {
            $registry->register(new RenderableDefinitionData(
                key: $assetComponent->value,
                type: RenderableTypeEnum::Asset,
                blade: match ($assetComponent) {
                    FrontendComponentKeyEnum::SectionBlock => 'capell::components.section.block',
                    FrontendComponentKeyEnum::SectionTeamMember => 'capell::components.section.team-member',
                },
            ));
        }

        $registry->register(new RenderableDefinitionData(
            key: LivewireComponentsEnum::PagesBlock->value,
            type: 'layout-block',
            livewire: 'capell::block.pages',
        ));
    }

    private function registerRenderHooks(): void
    {
        App::afterResolving(
            RenderHookRegistry::class,
            function (RenderHookRegistry $registry): void {
                $this->registerRenderHooksForRegistry($registry);
            },
        );

        if (App::bound(RenderHookRegistry::class)) {
            $this->registerRenderHooksForRegistry(App::make(RenderHookRegistry::class));
        }
    }

    private function registerRenderHooksForRegistry(RenderHookRegistry $registry): void
    {
        $registryId = spl_object_id($registry);

        if (isset(self::$mainContentHookRegistries[$registryId])) {
            return;
        }

        (new RegisterMainContentLayoutHook($registry))->register();

        self::$mainContentHookRegistries[$registryId] = true;
    }

    private function registerListeners(): void
    {
        CapellCore::subscriberManager()->subscribe(AfterRecordSaved::class);
        CapellCore::subscriberManager()->subscribe(SiteTreeRebuilt::class);
        CapellCore::subscriberManager()->subscribe(LayoutLoaded::class);
    }

    private function registerCloneableRelations(): void
    {
        CapellCore::addCloneableRelations('page', 'blockAssets');
    }
}
