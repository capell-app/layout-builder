<?php

declare(strict_types=1);

use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

// Adds blog package providers and migrations for the --include-hero test path that queries articles.
trait WithBlogMigrations
{
    protected function getPackageProviders(mixed $app): array
    {
        return [...parent::getPackageProviders($app), BlogServiceProvider::class];
    }

    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
    }
}

uses(WithBlogMigrations::class);

it('runs the demo command and creates demo layouts for a site', function (): void {
    $capellDirectory = storage_path('app/capell');
    $demoDirectory = $capellDirectory . '/demo';

    File::deleteDirectory($demoDirectory);

    $sourceDemoDirectory = realpath(__DIR__ . '/../../../../../demo');

    throw_if($sourceDemoDirectory === false, RuntimeException::class, 'Demo fixtures directory not found.');

    $demoCopiedToStorage = File::copyDirectory($sourceDemoDirectory, $demoDirectory);

    expect($demoCopiedToStorage)->toBeTrue();

    $demoImgPath = $demoDirectory . DIRECTORY_SEPARATOR . 'img';

    File::spy();
    $dummyFile = new SplFileInfo($demoImgPath . DIRECTORY_SEPARATOR . 'home.jpg');
    File::shouldReceive('files')->with($demoImgPath)->andReturn([$dummyFile]);
    File::shouldReceive('exists')->with(Mockery::on(fn (string $path): bool => str_starts_with($path, $demoImgPath)))->andReturn(false);

    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->withTranslations($language)->state(['name' => 'Test'])->create();
    $type = Type::factory()->page()->default()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    Page::factory()->count(4)->site($site)->has(Media::factory())->create();

    artisan('capell:mosaic-demo', [
        '--sites' => $site->name,
    ])
        ->expectsOutput('Running hero demo...')
        ->assertExitCode(0);
});

it('runs mosaic demo with --skip-hero without delegating to hero demo', function (): void {
    $capellDirectory = storage_path('app/capell');
    $demoDirectory = $capellDirectory . '/demo';

    File::deleteDirectory($demoDirectory);

    $sourceDemoDirectory = realpath(__DIR__ . '/../../../../../demo');

    throw_if($sourceDemoDirectory === false, RuntimeException::class, 'Demo fixtures directory not found.');

    $demoCopiedToStorage = File::copyDirectory($sourceDemoDirectory, $demoDirectory);

    expect($demoCopiedToStorage)->toBeTrue();

    $demoImgPath = $demoDirectory . DIRECTORY_SEPARATOR . 'img';

    File::spy();
    $dummyFile = new SplFileInfo($demoImgPath . DIRECTORY_SEPARATOR . 'home.jpg');
    File::shouldReceive('files')->with($demoImgPath)->andReturn([$dummyFile]);
    File::shouldReceive('exists')->with(Mockery::on(fn (string $path): bool => str_starts_with($path, $demoImgPath)))->andReturn(false);

    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->withTranslations($language)->state(['name' => 'Test'])->create();
    Type::factory()->page()->default()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    Page::factory()->count(4)->site($site)->has(Media::factory())->create();

    artisan('capell:mosaic-demo', [
        '--sites' => $site->name,
        '--skip-hero' => true,
    ])
        ->doesntExpectOutput('Running hero demo...')
        ->assertExitCode(0);
});

it('runs mosaic demo when no pages with images exist yet', function (): void {
    $capellDirectory = storage_path('app/capell');
    $demoDirectory = $capellDirectory . '/demo';

    File::deleteDirectory($demoDirectory);

    $sourceDemoDirectory = realpath(__DIR__ . '/../../../../../demo');

    throw_if($sourceDemoDirectory === false, RuntimeException::class, 'Demo fixtures directory not found.');

    $demoCopiedToStorage = File::copyDirectory($sourceDemoDirectory, $demoDirectory);

    expect($demoCopiedToStorage)->toBeTrue();

    $demoImgPath = $demoDirectory . DIRECTORY_SEPARATOR . 'img';

    File::spy();
    $dummyFile = new SplFileInfo($demoImgPath . DIRECTORY_SEPARATOR . 'home.jpg');
    File::shouldReceive('files')->with($demoImgPath)->andReturn([$dummyFile]);
    File::shouldReceive('exists')->with(Mockery::on(fn (string $path): bool => str_starts_with($path, $demoImgPath)))->andReturn(false);

    $language = Language::factory()->english()->create();
    $site = Site::factory()->recycle($language)->withTranslations($language)->state(['name' => 'Test'])->create();
    Type::factory()->page()->default()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    artisan('capell:mosaic-demo', [
        '--sites' => $site->name,
        '--skip-hero' => true,
    ])
        ->doesntExpectOutput('Running hero demo...')
        ->assertExitCode(0);
});
