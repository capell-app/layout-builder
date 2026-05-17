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
use Capell\LayoutBuilder\Contracts\PublicElementPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadResolver;
use Capell\LayoutBuilder\Enums\ComponentTypeEnum;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\LivewireComponentsEnum;
use Capell\LayoutBuilder\Listeners\AfterRecordSaved;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Listeners\LayoutSavingListener;
use Capell\LayoutBuilder\Listeners\SiteTreeRebuilt;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
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
        App::bind(PublicElementPayloadResolver::class, DefaultPublicElementPayloadResolver::class);
        App::tag([], PublicElementPayloadContributor::TAG);
    }

    private function registerModelExtensions(): void
    {
        Layout::addFillable(['elements']);
        Layout::addCasts(['elements' => 'array']);
    }

    private function registerRelationships(): void
    {
        Layout::resolveRelationUsing(
            'layoutElements',
            fn (Layout $model): BelongsToJson => $model->belongsToJson(
                Element::class,
                'elements',
                'key',
            ),
        );

        Page::resolveRelationUsing(
            'elementAssets',
            fn (Page $model): MorphMany => $model->morphMany(ElementAsset::class, 'pageable'),
        );

        Page::resolveRelationUsing(
            'elements',
            fn (Page $model): MorphToMany => $model->morphToMany(
                Element::class,
                'asset',
                'layout_element_assets',
                'asset_id',
                'layout_element_id',
            )
                ->wherePivot('asset_type', $model->getMorphClass()),
        );

        Blueprint::resolveRelationUsing(
            'elements',
            fn (Blueprint $model): HasMany => $model->hasMany(Element::class, 'blueprint_id'),
        );
    }

    private function registerModelEvents(): void
    {
        Layout::saving(resolve(LayoutSavingListener::class));

        Blueprint::updated(function (Blueprint $type): void {
            $rawType = $type->getRawOriginal('type');

            if ($rawType !== LayoutTypeEnum::Element->value) {
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

        foreach (ElementComponentEnum::cases() as $elementComponent) {
            $blade = match ($elementComponent) {
                ElementComponentEnum::AssetAccordion => 'capell::element.asset.accordion',
                ElementComponentEnum::AssetBanner => 'capell::element.asset.banners',
                ElementComponentEnum::AssetBlock => 'capell::element.asset.blocks',
                ElementComponentEnum::AssetCarousel => 'capell::element.asset.carousel',
                ElementComponentEnum::AssetFeatures => 'capell::element.asset.features',
                ElementComponentEnum::AssetMedia => 'capell::element.asset.media',
                ElementComponentEnum::AssetTestimonials => 'capell::element.asset.testimonials',
                ElementComponentEnum::AnnouncementBar => 'capell::element.announcement-bar',
                ElementComponentEnum::Assets => 'capell::element.asset',
                ElementComponentEnum::BannerImage => 'capell::element.banner-image',
                ElementComponentEnum::Default => 'capell::element.default',
                ElementComponentEnum::Hero => 'capell::element.hero',
                ElementComponentEnum::Navigation => 'capell::element.navigation',
                ElementComponentEnum::NavigationTabs => 'capell::element.navigation.tabs',
                ElementComponentEnum::PageBreadcrumbs => 'capell::element.page.breadcrumbs',
                ElementComponentEnum::PageChildren => 'capell::element.page.children',
                ElementComponentEnum::PageContent => 'capell::element.page.content',
                ElementComponentEnum::PageLatest => 'capell::element.page.latest',
                ElementComponentEnum::PageSiblings => 'capell::element.page.siblings',
                ElementComponentEnum::PageSlot => 'capell::element.slot',
                ElementComponentEnum::Pages => 'capell::element.asset.pages',
                ElementComponentEnum::Snippet => 'capell::element.snippet',
                ElementComponentEnum::ApHeroBanner => 'capell::element.modern.hero-banner',
                ElementComponentEnum::ApCardGrid => 'capell::element.modern.card-grid',
                ElementComponentEnum::ApFeatureList => 'capell::element.modern.feature-list',
                ElementComponentEnum::ApCTASection => 'capell::element.modern.cta-section',
                ElementComponentEnum::ApImageGallery => 'capell::element.modern.image-gallery',
                ElementComponentEnum::ApTeamMembers => 'capell::element.modern.team-members',
                ElementComponentEnum::ApPricingTable => 'capell::element.modern.pricing-table',
                ElementComponentEnum::ApTestimonials => 'capell::element.modern.testimonials',
                ElementComponentEnum::ApFaqSection => 'capell::element.modern.faq-section',
                ElementComponentEnum::ApStatsSection => 'capell::element.modern.stats-section',
                ElementComponentEnum::ApAlternatingContent => 'capell::element.modern.alternating-content',
                ElementComponentEnum::ApProcessSteps => 'capell::element.modern.process-steps',
            };

            $registry->register(new RenderableDefinitionData(
                key: $elementComponent->value,
                type: RenderableTypeEnum::Element,
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
            key: LivewireComponentsEnum::PagesElement->value,
            type: RenderableTypeEnum::Element,
            livewire: 'capell::element.pages',
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
        CapellCore::addCloneableRelations('page', 'elementAssets');
    }
}
