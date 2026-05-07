<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Actions\CreateLayoutBuilderDemoSiteAction;
use Capell\LayoutBuilder\Actions\InstallPackageAction;
use Capell\LayoutBuilder\Data\DemoSitePlanData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $capellDirectory = storage_path('app/capell');
    $demoDirectory = $capellDirectory . '/demo';

    File::deleteDirectory($demoDirectory);

    $sourceDemoDirectory = realpath(__DIR__ . '/../../../../../packages/demo-kit/demo');

    throw_if($sourceDemoDirectory === false, RuntimeException::class, 'Demo fixtures directory not found.');

    expect(File::copyDirectory($sourceDemoDirectory, $demoDirectory))->toBeTrue();
});

it('creates demo content, homepage sections, media-backed widgets, and navigations for a site', function (): void {
    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->default()->withTranslations($language)->state(['name' => 'Test'])->create();

    Type::factory()->page()->default()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    Page::factory()->count(4)->site($site)->has(Media::factory())->withTranslations($language)->create();

    InstallPackageAction::run();

    $created = CreateLayoutBuilderDemoSiteAction::run(new DemoSitePlanData(
        site: $site,
        contentTree: config('capell-demo-kit.pages')[0],
    ));

    $homePage = $site->refresh()->getHomePage();
    $homeLayout = Layout::query()->firstWhere('key', LayoutEnum::Home);
    $galleryWidget = Widget::query()->where('key', 'gallery')->firstOrFail();
    $exampleNavigationWidget = Widget::query()->where('key', 'example-navigation')->firstOrFail();

    expect($created)->toBeTrue()
        ->and($homePage->layout_id)->toBe($homeLayout?->id)
        ->and($homeLayout?->containers)->toHaveKeys(['main', 'faq-main', 'faq-col', 'secondary', 'ap-widgets', 'split-two'])
        ->and(DB::table('sections')->where('site_id', $site->id)->count())->toBeGreaterThan(0)
        ->and(DB::table('sections')->where('name', 'FAQs')->exists())->toBeTrue()
        ->and($galleryWidget->assets()->count())->toBeGreaterThan(0)
        ->and($exampleNavigationWidget->translations()->count())->toBe(1)
        ->and(Navigation::query()->where('site_id', $site->id)->whereIn('key', [
            NavigationHandle::Main->value,
            NavigationHandle::Footer->value,
            NavigationHandle::SubFooter->value,
            'example-menu',
        ])->count())->toBe(4);
});

it('delegates site navigation composition to the navigation demo adapter', function (): void {
    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->default()->withTranslations($language)->state(['name' => 'Adapter Test'])->create();

    Type::factory()->page()->default()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    Page::factory()->count(2)->site($site)->withTranslations($language)->create();

    InstallPackageAction::run();

    $navigationDemoCreator = Mockery::mock(NavigationDemoCreator::class);
    $navigationDemoCreator->shouldReceive('setupMainNavigation')->once();
    $navigationDemoCreator->shouldReceive('setupFooterNavigation')->once();
    $navigationDemoCreator->shouldReceive('setupSubFooterNavigation')->once();

    app()->instance(NavigationDemoCreator::class, $navigationDemoCreator);

    CreateLayoutBuilderDemoSiteAction::run(new DemoSitePlanData(
        site: $site,
        contentTree: config('capell-demo-kit.pages')[0],
    ));
});
