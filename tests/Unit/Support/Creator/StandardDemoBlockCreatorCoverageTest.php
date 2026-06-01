<?php

declare(strict_types=1);

use Capell\Core\Data\PageTypeData;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\BlockCreator;
use Capell\LayoutBuilder\Support\Creator\StandardDemoBlockCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LayoutBuilderStandardDemoContentPage extends Page
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

final class LayoutBuilderStandardDemoBlockCreatorHarness extends StandardDemoBlockCreator
{
    public function __construct()
    {
        $this->contentModel = LayoutBuilderStandardDemoContentPage::class;
        $this->blockModel = Widget::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }

    /**
     * @param  Collection<array-key, mixed>  $siteTree
     * @return array<array-key, mixed>
     */
    public function exposeNavigationPageItems(Collection $siteTree, Language $language): array
    {
        return $this->navigationPageItems($siteTree, $language);
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public function exposeCreateFeatures(Site $site): Collection
    {
        return $this->createFeatures($site);
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @return Collection<array-key, mixed>
     */
    public function exposeCreateTestimonials(Collection $languages): Collection
    {
        return $this->createTestimonials($languages);
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @return Collection<array-key, mixed>
     */
    public function exposeCreateTeamMembers(Collection $languages): Collection
    {
        return $this->createTeamMembers($languages);
    }

    #[Override]
    protected function createMedia(Model $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): void {}

    #[Override]
    protected function createBlockMedia(Widget $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): Media
    {
        $content = LayoutBuilderStandardDemoContentPage::query()->create([
            'name' => $name ?? 'Demo Media',
        ]);

        $model->assets()->create([
            'asset_id' => $content->getKey(),
            'asset_type' => $content->getMorphClass(),
        ]);

        return Media::factory()->create([
            'model_type' => $content->getMorphClass(),
            'model_id' => $content->getKey(),
            'collection_name' => $collection instanceof BackedEnum ? $collection->value : $collection,
        ]);
    }
}

/**
 * @return array<array-key, mixed>
 */
function prepareStandardDemoCreatorHarness(Language $language): array
{
    if (! CapellCore::hasPageType('section')) {
        CapellCore::registerPageType(new PageTypeData(
            name: 'section',
            model: LayoutBuilderStandardDemoContentPage::class,
            label: 'Section',
        ));
    }

    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();

    resolve(TypeCreator::class)->createBlockTypes();
    resolve(TypeCreator::class)->createDefaultContentType();
    resolve(TypeCreator::class)->createBuilderContentType();

    $defaultPageType = resolve(BlueprintCreator::class)->defaultPageType();
    $layout = Layout::factory()->default()->create();

    LayoutBuilderStandardDemoContentPage::$defaultSiteId = $site->getKey();
    LayoutBuilderStandardDemoContentPage::$defaultLayoutId = $layout->getKey();
    LayoutBuilderStandardDemoContentPage::$defaultBlueprintId = $defaultPageType->getKey();

    return [$site, $layout, $defaultPageType, new LayoutBuilderStandardDemoBlockCreatorHarness];
}

it('builds standard content, faq, gallery, carousel, and navigation demo blocks', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [$site, $layout, $defaultPageType, $creator] = prepareStandardDemoCreatorHarness($language);

    $homePage = Page::factory()
        ->home()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()]);

    Page::factory()
        ->count(3)
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()])
        ->each(function (Page $page): void {
            Media::factory()->model($page)->collection(MediaCollectionEnum::Image)->create();
        });

    $languages = new Collection([$language]);

    $contentBlock = $creator->createContentBlock($languages);
    $splitContentBlock = $creator->createSplitContentBlock($languages);
    $faqBlock = $creator->createFaqBlock($languages);
    $galleryBlock = $creator->createGalleryBlock();
    $carouselBlock = $creator->createMediaCarouselBlock();
    $navigationBlock = $creator->createStaticNavigationBlock($languages, $site);
    $pageCardsBlock = $creator->createPageCardsBlock($homePage, occurrence: 3);

    expect($contentBlock->key)->toBe('example-content')
        ->and($splitContentBlock->key)->toBe('example-split-content')
        ->and($faqBlock->key)->toBe('faq')
        ->and($faqBlock->assets()->count())->toBe(6)
        ->and($galleryBlock->assets()->count())->toBe(5)
        ->and($carouselBlock->assets()->count())->toBe(8)
        ->and($navigationBlock->meta['navigation'])->toBe('example-menu')
        ->and($pageCardsBlock->assets()->where('occurrence', 3)->count())->toBe(3);
});

it('creates faq blocks for languages without explicit demo questions', function (): void {
    $language = Language::factory()->create(['code' => 'nl']);

    [, , , $creator] = prepareStandardDemoCreatorHarness($language);

    $faqBlock = $creator->createFaqBlock(new Collection([$language]));

    expect($faqBlock->assets()->count())->toBe(6)
        ->and(LayoutBuilderStandardDemoContentPage::query()->where('name', 'How was this website created?')->exists())->toBeTrue();
});

it('exercises base demo creator content collections and recursive navigation labels', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [$site, $layout, $defaultPageType, $creator] = prepareStandardDemoCreatorHarness($language);

    $parentPage = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create([
            'name' => 'Parent Page',
            'layout_id' => $layout->getKey(),
            'blueprint_id' => $defaultPageType->getKey(),
        ]);

    $childPage = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create([
            'name' => 'Child Page',
            'parent_id' => $parentPage->getKey(),
            'layout_id' => $layout->getKey(),
            'blueprint_id' => $defaultPageType->getKey(),
        ]);

    $parentPage->setRelation('children', new Illuminate\Database\Eloquent\Collection([$childPage]));

    $items = $creator->exposeNavigationPageItems(new Collection([$parentPage]), $language);
    $features = $creator->exposeCreateFeatures($site);
    $testimonials = $creator->exposeCreateTestimonials(new Collection([$language]));
    $teamMembers = $creator->exposeCreateTeamMembers(new Collection([$language]));

    $firstNavigationItem = array_values($items)[0];

    expect($firstNavigationItem['label'])->toBe($parentPage->translation?->title)
        ->and($firstNavigationItem['children'])->toHaveCount(1)
        ->and($features)->toHaveCount(7)
        ->and($testimonials)->toHaveCount(3)
        ->and($teamMembers)->toHaveCount(16);
});

it('skips duplicate page card and content block assets for existing scoped records', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [$site, $layout, $defaultPageType, $creator] = prepareStandardDemoCreatorHarness($language);

    $page = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()]);

    $relatedPage = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()]);

    Media::factory()->model($relatedPage)->collection(MediaCollectionEnum::Image)->create();

    $pageCardsBlock = resolve(BlockCreator::class)->pagesCardBlock();
    $pageCardsBlock->assets()->create([
        'asset_id' => $relatedPage->getKey(),
        'asset_type' => $relatedPage->getMorphClass(),
        'pageable_id' => $page->getKey(),
        'pageable_type' => $page->getMorphClass(),
        'container' => 'main',
        'occurrence' => 1,
    ]);

    $creator->createPageCardsBlock($page);

    $contentBlock = resolve(BlockCreator::class)->assetsBlock();
    $creator->createContentsBlock($contentBlock, $page, 'secondary');
    $creator->createContentsBlock($contentBlock, $page, 'secondary');

    expect($pageCardsBlock->assets()->count())->toBe(1)
        ->and($contentBlock->assets()->where([
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => 'secondary',
            'occurrence' => 1,
        ])->count())->toBe(4);
});

it('creates standard demo collection blocks with translations and reusable assets', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [, , , $creator] = prepareStandardDemoCreatorHarness($language);

    $languages = new Collection([$language]);

    $clientLogosBlock = $creator->createClientLogosBlock($languages);
    $businessFeaturesBlock = $creator->createBusinessFeaturesBlock(Site::getDefault());
    $bannersBlock = $creator->createBannersBlock();
    $testimonialsBlock = $creator->createTestimonialsBlock($languages);
    $statisticsBlock = $creator->createStatisticsBlock();
    $teamPortfolioBlock = $creator->createTeamPortfolioBlock($languages);

    expect($clientLogosBlock->key)->toBe('client-logos')
        ->and($clientLogosBlock->assets()->count())->toBe(12)
        ->and($clientLogosBlock->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($businessFeaturesBlock->key)->toBe('business-features')
        ->and($businessFeaturesBlock->assets()->count())->toBe(7)
        ->and($bannersBlock->assets()->count())->toBe(7)
        ->and($testimonialsBlock->assets()->count())->toBe(3)
        ->and($statisticsBlock->assets()->count())->toBe(4)
        ->and($teamPortfolioBlock->assets()->count())->toBe(16);
});
