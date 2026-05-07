<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Support\Creator\PageCreator;
use Capell\StarterSites\Support\Creator\DemoCreator;
use Capell\StarterSites\Support\Extensions\StarterSitesActionSchemaRegistry;
use Capell\StarterSites\Tests\Fixtures\Commands\TrackingDemoCommand;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

it('creates full multi site and language demo data and runs package demos', function (): void {
    TrackingDemoCommand::reset();

    CapellCore::registerPackage(name: 'vendor/example-package');
    CapellCore::forcePackageInstalled('vendor/example-package');
    CapellCore::getPackage('vendor/example-package')->demoCommand = 'test:demo';
    CapellCore::getPackage('vendor/example-package')->demoParams = ['url', 'user', 'languages', 'sites'];

    Artisan::registerCommand(new TrackingDemoCommand);

    app()->bind(PageCreator::class, function (): PageCreator {
        $mock = Mockery::mock(PageCreator::class . '[createHomePage,createErrorPage]');
        $mock->shouldReceive('createHomePage')->andReturnUsing(fn (): Page => new Page);
        $mock->shouldReceive('createErrorPage')->andReturnUsing(fn (): Page => new Page);

        return $mock;
    });

    app()->bind(DemoCreator::class, function (Application $app, array $params): DemoCreator {
        $mock = Mockery::mock(DemoCreator::class . '[setupRelatedSites,createPage,setupSite]', [$params['url'], $params['author']]);
        $mock->shouldReceive('setupRelatedSites')->andReturnNull();
        $mock->shouldReceive('createPage')->andReturnUsing(fn (): Page => new Page);
        $mock->shouldReceive('setupSite')->andReturnNull();

        return $mock;
    });

    test()->artisan('capell:starter-sites-full-demo', [
        '--url' => 'https://example.test',
        '--languages' => 'en,fr',
        '--sites' => 'Main Site,Sub Site',
        '--force' => true,
    ])->assertExitCode(0);

    expect(TrackingDemoCommand::$executionOrder)->toBe(['test:demo']);
});

it('requires force when running non interactively', function (): void {
    test()->artisan('capell:starter-sites-full-demo', [
        '--url' => 'https://example.test',
        '--no-interaction' => true,
    ])->assertExitCode(1);
});

it('registers a cms extension action schema for full demo data', function (): void {
    $schema = resolve(StarterSitesActionSchemaRegistry::class)->get('demo');

    expect($schema)
        ->toHaveCount(3)
        ->and($schema[0])->toBeInstanceOf(TextInput::class)
        ->and($schema[1])->toBeInstanceOf(LanguageSelect::class)
        ->and($schema[2])->toBeInstanceOf(SiteSelect::class);
});
