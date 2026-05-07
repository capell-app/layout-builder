<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\DemoKit\Providers\DemoKitServiceProvider;
use Capell\DemoKit\Tests\Fixtures\Commands\TrackingDemoCommand;
use Illuminate\Support\Facades\Artisan;

it('runs core demo command successfully', function (): void {
    TrackingDemoCommand::reset();

    CapellCore::registerPackage(name: 'vendor/example-package');

    $package = CapellCore::getPackage('vendor/example-package');
    $package->demoCommand = 'test:demo';
    $package->demoParams = ['url', 'user', 'languages', 'sites'];

    Artisan::registerCommand(new TrackingDemoCommand);

    test()->artisan('capell:demo', [
        '--url' => 'https://example.test',
        '--user' => true,
        '--languages' => 'en,fr',
        '--sites' => 'Main Site,Sub Site',
        '--packages' => 'vendor/example-package',
    ])
        ->expectsQuestion('Are you sure you want to install example site content?', true)
        ->assertExitCode(0);

    expect(TrackingDemoCommand::$executionOrder)->toBe(['test:demo']);
});

it('runs demo commands in package workflow order', function (): void {
    TrackingDemoCommand::reset();

    CapellCore::registerPackage(name: 'capell-app/worktree');
    CapellCore::registerPackage(name: 'capell-app/blog');
    CapellCore::registerPackage(name: 'capell-app/form-builder');
    CapellCore::registerPackage(name: DemoKitServiceProvider::$packageName);

    CapellCore::getPackage('capell-app/worktree')->demoCommand = 'worktree:demo';
    CapellCore::getPackage('capell-app/worktree')->sort = 1;
    CapellCore::getPackage('capell-app/blog')->demoCommand = 'blog:demo';
    CapellCore::getPackage('capell-app/blog')->sort = 30;
    CapellCore::getPackage('capell-app/form-builder')->demoCommand = 'form-builder:demo';
    CapellCore::getPackage('capell-app/form-builder')->sort = 10;

    Artisan::registerCommand(new TrackingDemoCommand('worktree:demo {--url=} {--user=} {--languages=*} {--sites=*}'));
    Artisan::registerCommand(new TrackingDemoCommand('blog:demo {--url=} {--user=} {--languages=*} {--sites=*}'));
    Artisan::registerCommand(new TrackingDemoCommand('form-builder:demo {--url=} {--user=} {--languages=*} {--sites=*}'));

    test()->artisan('capell:demo', [
        '--url' => 'https://example.test',
        '--packages' => 'capell-app/worktree,capell-app/blog,capell-app/form-builder',
        '--sites' => 'Main Site',
        '--languages' => 'en',
        '--force' => true,
    ])->assertExitCode(0);

    expect(TrackingDemoCommand::$executionOrder)->toBe([
        'form-builder:demo',
        'blog:demo',
        'worktree:demo',
    ]);
});
