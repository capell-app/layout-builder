<?php

declare(strict_types=1);

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\ApDemoBlockCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class LayoutBuilderDemoContentPage extends Page
{
    public static int $defaultSiteId;

    public static int $defaultLayoutId;

    public static int $defaultBlueprintId;

    protected $table = 'pages';

    #[Override]
    public function getMorphClass(): string
    {
        return (new Page)->getMorphClass();
    }

    #[Override]
    protected static function booted(): void
    {
        self::creating(function (self $page): void {
            $page->uuid ??= Str::uuid()->toString();
            $page->site_id ??= self::$defaultSiteId;
            $page->layout_id ??= self::$defaultLayoutId;
            $page->blueprint_id ??= self::$defaultBlueprintId;
        });
    }
}

final class LayoutBuilderDemoBlockCreatorHarness extends ApDemoBlockCreator
{
    public function __construct()
    {
        $this->contentModel = LayoutBuilderDemoContentPage::class;
        $this->blockModel = Widget::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }

    #[Override]
    protected function createMedia(Model $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): void {}

    #[Override]
    protected function createBlockMedia(Widget $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): Media
    {
        return Media::factory()->create([
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'collection_name' => is_string($collection) ? $collection : $collection->value,
        ]);
    }
}

it('creates modern and application preview demo blocks with asset-backed content', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();

    resolve(TypeCreator::class)->createBlockTypes();
    $defaultPageType = resolve(BlueprintCreator::class)->defaultPageType();
    $layout = Layout::factory()->default()->create();

    LayoutBuilderDemoContentPage::$defaultSiteId = $site->getKey();
    LayoutBuilderDemoContentPage::$defaultLayoutId = $layout->getKey();
    LayoutBuilderDemoContentPage::$defaultBlueprintId = $defaultPageType->getKey();

    $creator = new LayoutBuilderDemoBlockCreatorHarness;

    $blocks = [
        $creator->createModernFeatureListBlock(),
        $creator->createModernTeamMembersBlock(),
        $creator->createModernPricingTableBlock(),
        $creator->createModernTestimonialsBlock(),
        $creator->createModernFaqBlock(),
        $creator->createModernStatsSectionBlock(),
        $creator->createModernAlternatingContentBlock(),
        $creator->createModernProcessStepsBlock(),
        $creator->createModernImageGalleryBlock(),
        $creator->createApHeroBannerBlock(),
        $creator->createApCardGridBlock(),
        $creator->createApFeatureListBlock(),
        $creator->createFeatureListBlock(),
        $creator->createApCtaSectionBlock(),
        $creator->createApImageGalleryBlock(),
    ];

    $apCtaSectionBlock = capell_test_instance(Widget::query()->firstWhere('key', 'ap-cta-section'), Widget::class);
    $apCtaSectionBlockMeta = capell_test_array($apCtaSectionBlock->meta);
    $apImageGalleryBlock = capell_test_instance(Widget::query()->firstWhere('key', 'ap-image-gallery'), Widget::class);
    $apImageGalleryBlockMeta = capell_test_array($apImageGalleryBlock->meta);

    expect($blocks)
        ->each->toBeInstanceOf(Widget::class)
        ->and(Widget::query()->firstWhere('key', 'modern-feature-list')?->assets()->count())->toBe(6)
        ->and(Widget::query()->firstWhere('key', 'modern-team-members')?->assets()->count())->toBe(3)
        ->and(Widget::query()->firstWhere('key', 'modern-pricing-table')?->assets()->count())->toBe(3)
        ->and(Widget::query()->firstWhere('key', 'modern-faq')?->assets()->count())->toBe(5)
        ->and(Widget::query()->firstWhere('key', 'ap-card-grid')?->assets()->count())->toBe(3)
        ->and(Widget::query()->firstWhere('key', 'ap-feature-list')?->assets()->count())->toBe(4)
        ->and($apCtaSectionBlockMeta['primary_button_text'] ?? null)->toBe('Get Started Free')
        ->and($apImageGalleryBlockMeta['lightbox'] ?? null)->toBeTrue()
        ->and($apImageGalleryBlock->media()->count())->toBe(6);
});

it('creates page card assets for related pages in the requested occurrence', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();

    resolve(TypeCreator::class)->createBlockTypes();
    resolve(TypeCreator::class)->createDefaultContentType();
    resolve(BlueprintCreator::class)->defaultPageType();

    $homePage = Page::factory()
        ->home()
        ->site($site)
        ->withTranslations($language)
        ->create();

    $relatedPages = Page::factory()
        ->count(3)
        ->site($site)
        ->withTranslations($language)
        ->create();

    $relatedPages->each(function (Page $page): void {
        Media::factory()
            ->model($page)
            ->collection(MediaCollectionEnum::Image)
            ->create();
    });

    $creator = new LayoutBuilderDemoBlockCreatorHarness;

    $block = $creator->createPageCardsBlock($homePage, container: 'related', occurrence: 2);

    expect($block->assets()->where([
        'pageable_id' => $homePage->getKey(),
        'pageable_type' => $homePage->getMorphClass(),
        'container' => 'related',
        'occurrence' => 2,
    ])->count())->toBe(3);
});
