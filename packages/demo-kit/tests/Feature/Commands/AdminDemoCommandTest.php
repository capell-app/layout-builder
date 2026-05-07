<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Support\Creator\PageCreator;
use Capell\DemoKit\Support\Creator\DemoCreator;
use Capell\DemoKit\Support\Creator\DemoResourceResolver;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

it('runs demo command successfully', function (): void {
    $user = User::factory()->create(['email' => 'admin@example.com']);
    $demoDirectory = storage_path('framework/testing/demo-kit-demo');

    File::ensureDirectoryExists($demoDirectory . '/img');
    File::ensureDirectoryExists($demoDirectory . '/video');
    $image = imagecreatetruecolor(4, 4);
    imagejpeg($image, $demoDirectory . '/img/demo.jpg');
    imagedestroy($image);

    $zipPath = tempnam(sys_get_temp_dir(), 'capell-demo-kit-') . '.zip';
    $archive = new ZipArchive;
    $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('demo/img/.gitkeep', '');
    $archive->addFromString('demo/video/.gitkeep', '');
    $archive->close();

    config()->set('capell-demo-kit.archive.checksum', hash_file('sha256', $zipPath));
    config()->set('capell-demo.archive.checksum', hash_file('sha256', $zipPath));

    Http::fake([
        '*' => Http::response(File::get($zipPath), 200),
    ]);

    app()->instance(DemoResourceResolver::class, new class($demoDirectory)
    {
        public function __construct(private readonly string $demoDirectory) {}

        public function resolve(?string $folder): string
        {
            $folder = in_array($folder, [null, '', '0'], true) ? null : ltrim($folder, '/');

            return $this->demoDirectory . ($folder === null ? '' : '/' . $folder);
        }

        public function ensureStorageDemoResources(): string
        {
            return $this->demoDirectory;
        }

        public function assertSafeDemoZipEntries(ZipArchive $zip): void {}
    });

    app()->bind(PageCreator::class, function (): PageCreator {
        $mock = Mockery::mock(PageCreator::class . '[createHomePage,createErrorPage]');
        $mock->shouldReceive('createHomePage')->andReturnUsing(fn (): Page => new Page);
        $mock->shouldReceive('createErrorPage')->andReturnUsing(fn (): Page => new Page);

        return $mock;
    });

    app()->bind(DemoCreator::class, function (Application $app, array $params): DemoCreator {
        $mock = Mockery::mock(DemoCreator::class . '[setupRelatedSites,createPage,setupSite,setupMainNavigation,setupFooterNavigation,subFooterNavigation]', [$params['url'], $params['author']]);
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('setupRelatedSites')->andReturnNull();
        $mock->shouldReceive('createPage')->andReturnUsing(fn (): Page => new Page);
        $mock->shouldReceive('setupSite')->andReturnNull();
        $mock->shouldReceive('setupMainNavigation')->andReturnNull();
        $mock->shouldReceive('setupFooterNavigation')->andReturnNull();
        $mock->shouldReceive('subFooterNavigation')->andReturnNull();

        return $mock;
    });

    test()->artisan('capell:admin-demo', [
        '--url' => 'https://example.test',
        '--user' => $user->email,
        '--languages' => 'en,fr',
        '--sites' => 'Main Site,Sub Site',
    ])->assertExitCode(0);

    File::delete($zipPath);
});
