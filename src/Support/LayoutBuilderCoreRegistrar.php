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
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\FrontendHookRegistrar;
use Capell\LayoutBuilder\Actions\InvalidateTypeLayoutPreviewImagesAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Enums\ComponentTypeEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\LivewireComponentsEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Listeners\AfterRecordSaved;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Listeners\SiteTreeRebuilt;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\DefaultLayoutInterceptor;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\HomeLayoutInterceptor;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\ResultsLayoutInterceptor;
use Capell\LayoutBuilder\Support\RenderHooks\RegisterMainContentLayoutHook;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;

final class LayoutBuilderCoreRegistrar
{
    public function register(): void
    {
        LayoutModelRegistrar::register();

        $this->registerManagers();
        $this->registerRelationships();
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
        App::bind(PublicLayoutWidgetPayloadResolver::class, DefaultPublicLayoutWidgetPayloadResolver::class);
        App::tag([], PublicLayoutWidgetPayloadContributor::TAG);
    }

    private function registerRelationships(): void
    {
        Page::resolveRelationUsing(
            'widgetAssets',
            fn (Page $model): MorphMany => $model->morphMany(WidgetAsset::class, 'pageable'),
        );

        Page::resolveRelationUsing(
            'widgets',
            fn (Page $model): MorphToMany => $model->morphToMany(
                Widget::class,
                'asset',
                'widget_assets',
                'asset_id',
                'widget_id',
            )
                ->wherePivot('asset_type', $model->getMorphClass()),
        );

        Blueprint::resolveRelationUsing(
            'widgets',
            fn (Blueprint $model): HasMany => $model->hasMany(Widget::class, 'blueprint_id'),
        );

    }

    private function registerModelEvents(): void
    {
        Blueprint::updated(function (Blueprint $type): void {
            $rawType = $type->getRawOriginal('type');

            if ($rawType !== LayoutTypeEnum::Widget->value) {
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

        foreach (WidgetComponentEnum::cases() as $widgetComponent) {
            $blade = match ($widgetComponent) {
                WidgetComponentEnum::AssetAccordion => 'capell::widget.asset.accordion',
                WidgetComponentEnum::AssetBanner => 'capell::widget.asset.banners',
                WidgetComponentEnum::AssetWidget => 'capell::widget.asset.widgets',
                WidgetComponentEnum::AssetCarousel => 'capell::widget.asset.carousel',
                WidgetComponentEnum::AssetFeatures => 'capell::widget.asset.features',
                WidgetComponentEnum::AssetMedia => 'capell::widget.asset.media',
                WidgetComponentEnum::AssetTestimonials => 'capell::widget.asset.testimonials',
                WidgetComponentEnum::AnnouncementBar => 'capell::widget.announcement-bar',
                WidgetComponentEnum::Assets => 'capell::widget.asset',
                WidgetComponentEnum::BannerImage => 'capell::widget.banner-image',
                WidgetComponentEnum::Default => 'capell::widget.default',
                WidgetComponentEnum::Hero => 'capell::widget.hero',
                WidgetComponentEnum::Navigation => 'capell::widget.navigation',
                WidgetComponentEnum::NavigationTabs => 'capell::widget.navigation.tabs',
                WidgetComponentEnum::PageBreadcrumbs => 'capell::widget.page.breadcrumbs',
                WidgetComponentEnum::PageChildren => 'capell::widget.page.children',
                WidgetComponentEnum::PageContent => 'capell::widget.page.content',
                WidgetComponentEnum::PageLatest => 'capell::widget.page.latest',
                WidgetComponentEnum::PageSiblings => 'capell::widget.page.siblings',
                WidgetComponentEnum::PageSlot => 'capell::widget.slot',
                WidgetComponentEnum::Pages => 'capell::widget.asset.pages',
                WidgetComponentEnum::Snippet => 'capell::widget.snippet',
                WidgetComponentEnum::ApHeroBanner => 'capell::widget.modern.hero-banner',
                WidgetComponentEnum::ApCardGrid => 'capell::widget.modern.card-grid',
                WidgetComponentEnum::ApFeatureList => 'capell::widget.modern.feature-list',
                WidgetComponentEnum::ApCTASection => 'capell::widget.modern.cta-section',
                WidgetComponentEnum::ApImageGallery => 'capell::widget.modern.image-gallery',
                WidgetComponentEnum::ApTeamMembers => 'capell::widget.modern.team-members',
                WidgetComponentEnum::ApPricingTable => 'capell::widget.modern.pricing-table',
                WidgetComponentEnum::ApTestimonials => 'capell::widget.modern.testimonials',
                WidgetComponentEnum::ApFaqSection => 'capell::widget.modern.faq-section',
                WidgetComponentEnum::ApStatsSection => 'capell::widget.modern.stats-section',
                WidgetComponentEnum::ApAlternatingContent => 'capell::widget.modern.alternating-content',
                WidgetComponentEnum::ApProcessSteps => 'capell::widget.modern.process-steps',
                WidgetComponentEnum::KitchenSinkRichText,
                WidgetComponentEnum::KitchenSinkStructuredText,
                WidgetComponentEnum::KitchenSinkDataDisplay,
                WidgetComponentEnum::KitchenSinkForms,
                WidgetComponentEnum::KitchenSinkInteractions,
                WidgetComponentEnum::KitchenSinkEmbeds,
                WidgetComponentEnum::KitchenSinkUtilityStates => 'capell::widget.kitchen-sink.reference',
            };

            $registry->register(new RenderableDefinitionData(
                key: $widgetComponent->value,
                type: 'layout-widget',
                blade: $blade,
            ));
        }

        foreach (FrontendComponentKeyEnum::cases() as $assetComponent) {
            $registry->register(new RenderableDefinitionData(
                key: $assetComponent->value,
                type: RenderableTypeEnum::Asset,
                blade: match ($assetComponent) {
                    FrontendComponentKeyEnum::SectionWidget => 'capell::components.section.widget',
                    FrontendComponentKeyEnum::SectionTeamMember => 'capell::components.section.team-member',
                },
            ));
        }

        $registry->register(new RenderableDefinitionData(
            key: LivewireComponentsEnum::PagesWidget->value,
            type: 'layout-widget',
            livewire: 'capell::widget.pages',
        ));
    }

    private function registerRenderHooks(): void
    {
        if (! App::bound(FrontendHookRegistrar::class)) {
            return;
        }

        resolve(FrontendHookRegistrar::class)->contribute(
            location: RenderHookLocation::MainContent,
            extension: new RegisterMainContentLayoutHook,
            owner: 'capell-app/layout-builder',
            key: 'main-content-layout',
            scenario: RegisterMainContentLayoutHook::Scenario,
            target: RegisterMainContentLayoutHook::Target,
            cacheSafe: true,
        );
    }

    private function registerListeners(): void
    {
        CapellCore::subscriberManager()->subscribe(AfterRecordSaved::class);
        CapellCore::subscriberManager()->subscribe(SiteTreeRebuilt::class);
        CapellCore::subscriberManager()->subscribe(LayoutLoaded::class);
    }

    private function registerCloneableRelations(): void
    {
        CapellCore::addCloneableRelations('page', 'widgetAssets');
    }
}
